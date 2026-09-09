<!-- LEFT EXECUTIVE NAVIGATION SIDEBAR (EXECUTIVE GRAY THEME WITH SHADOW) -->
<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative font-sans">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100 border-b border-slate-200/80">
        <a href="{{ url('/Forensic_Head') }}" class="flex items-center space-x-3 group text-decoration-none">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC LOGO.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-blue-600 font-bold tracking-wide uppercase">DEPT HEAD PORTAL</div>
            </div>
        </a>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold font-mono-code bg-blue-500/15 text-blue-700">
            EXEC
        </span>
    </div>

    <!-- NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        
        <!-- DIRECTORATE COMMAND -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-blue-600 tracking-wider mb-2">Directorate Command</div>
            <nav class="space-y-1 text-xs font-medium">
                
                <!-- DASHBOARD -->
                <a href="{{ url('/Forensic_Head') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('Forensic_Head') || request()->is('Forensic_Head/') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Forensic_Head') || request()->is('Forensic_Head/') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs">Dashboard</span>
                </a>

                <!-- CASE MANAGEMENT -->
                <a href="{{ url('/Forensic_Head/cases') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('Forensic_Head/cases*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Forensic_Head/cases*') ? 'text-white' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs flex-1">Case Management</span>
                    @php
                        $pendingCasesCountVal = (int)($pendingCasesCount ?? \App\Models\ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count());
                    @endphp
                    @if($pendingCasesCountVal > 0)
                        <span id="sidebarPendingCasesCount" class="ml-auto {{ request()->is('Forensic_Head/cases*') ? 'bg-white/20 text-white' : 'bg-red-500/15 text-red-700' }} font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $pendingCasesCountVal }}</span>
                    @endif
                </a>

                <!-- HEAD APPROVALS & SIGN-OFF -->
                <a href="{{ url('/Forensic_Head/approvals') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('Forensic_Head/approvals*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Forensic_Head/approvals*') ? 'text-white' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs flex-1">Head Approvals &amp; Sign-off</span>
                    @php
                        $pendingCountVal = (int)($pendingCount ?? \App\Models\ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count());
                    @endphp
                    @if($pendingCountVal > 0)
                        <span id="sidebarPendingBadgeCount" class="ml-auto {{ request()->is('Forensic_Head/approvals*') ? 'bg-white/20 text-white' : 'bg-amber-500/15 text-amber-800' }} font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $pendingCountVal }}</span>
                    @endif
                </a>

                <!-- 4 UNITS OVERSIGHT -->
                <a href="#" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('units-oversight*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('units-oversight*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs flex-1">4 Units Oversight</span>
                    <span class="ml-auto {{ request()->is('units-oversight*') ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }} font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">4 Units</span>
                </a>

                <!-- COURT EVIDENCE REPORTS -->
                <a href="#" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('court-evidence*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('court-evidence*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs flex-1">Court Evidence Reports</span>
                </a>

            </nav>
        </div>

        <!-- QUICK FILTER BY UNIT -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-600 tracking-wider mb-2">Quick Filter by Unit</div>
            <nav class="space-y-1 text-xs font-semibold">
                <button onclick="filterDeptHeadUnit('all', this)" class="sidebar-unit-btn w-full flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-left border-none bg-transparent cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400 mr-3 shrink-0"></span>
                    <span class="text-xs">All Units</span>
                </button>
                <button onclick="filterDeptHeadUnit('Crime Scene Support', this)" class="sidebar-unit-btn w-full flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-left border-none bg-transparent cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 mr-3 shrink-0 shadow-xs shadow-blue-600/40"></span>
                    <span class="text-xs">Crime Scene Support</span>
                </button>
                <button onclick="filterDeptHeadUnit('Evidence Management', this)" class="sidebar-unit-btn w-full flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-left border-none bg-transparent cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 mr-3 shrink-0 shadow-xs shadow-indigo-600/40"></span>
                    <span class="text-xs">Evidence Management</span>
                </button>
                <button onclick="filterDeptHeadUnit('LAB Analysis', this)" class="sidebar-unit-btn w-full flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-left border-none bg-transparent cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 mr-3 shrink-0 shadow-xs shadow-emerald-600/40"></span>
                    <span class="text-xs">LAB Analysis</span>
                </button>
                <button onclick="filterDeptHeadUnit('Digital', this)" class="sidebar-unit-btn w-full flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-left border-none bg-transparent cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-3 shrink-0 shadow-xs shadow-amber-500/40"></span>
                    <span class="text-xs">Digital &amp; Physical</span>
                </button>
            </nav>
        </div>

        <!-- DIRECTORATE GOVERNANCE -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-500 tracking-wider mb-2">Directorate Governance</div>
            <nav class="space-y-1 text-xs font-semibold">
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Personnel Roster (54 Staff)</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Audit Logs</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- DEPT HEAD PROFILE BANNER -->
    @php
        $authUser = auth()->user();
        $userName = $authUser->fullname ?? session('user_name') ?? 'Adomako Emmanuel';
        $nameParts = preg_split('/\s+/', trim($userName));
        $initials = count($nameParts) >= 2 
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($userName, 0, 2));
        $userRole = ($authUser && $authUser->is_head) ? 'Forensic Head · NACOC' : 'Commanding Officer · NACOC';
    @endphp
    <div class="p-3.5 bg-slate-200/50 space-y-2 border-t border-slate-200/80">
        <div class="flex items-center space-x-3 px-2 py-1">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-extrabold text-xs text-white shadow-sm shadow-emerald-600/30 shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-extrabold text-slate-900 truncate">{{ $userName }}</div>
                <div class="text-[10px] text-blue-600 font-mono-code font-bold truncate">{{ $userRole }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-white text-slate-800 hover:bg-red-600 hover:text-white text-xs font-bold transition-all shadow-sm cursor-pointer border border-slate-200/80">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Logout Terminal</span>
            </button>
        </form>
    </div>

</aside>