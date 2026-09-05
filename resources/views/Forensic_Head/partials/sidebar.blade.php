<!-- LEFT EXECUTIVE NAVIGATION SIDEBAR -->
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 border-r border-slate-800 select-none">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950">
        <a href="#" class="flex items-center space-x-3 group">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-600/30 group-hover:bg-blue-500 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <div class="font-extrabold text-white tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-blue-400 font-semibold tracking-wide uppercase">DEPT HEAD PORTAL</div>
            </div>
        </a>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-blue-500/20 text-blue-400 border border-blue-500/30">
            EXEC
        </span>
    </div>

    <!-- EXECUTIVE NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        
        <!-- EXECUTIVE MANAGEMENT -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-blue-400 tracking-wider mb-2">Directorate Command</div>
            <nav class="space-y-1 text-sm font-medium">
                <a href="{{ url('/Forensic_Head') }}" class="flex items-center px-3 py-2.5 rounded-xl text-white bg-blue-600 shadow-sm shadow-blue-600/30">
                    <svg class="w-5 h-5 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs font-bold">Executive Overview</span>
                </a>
                <a href="{{ url('/cases') }}" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Master Case Directory</span>
                    <span class="ml-auto bg-blue-500/20 text-blue-400 font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">148</span>
                </a>
                <a href="#pendingApprovals" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs">Head Approvals &amp; Sign-off</span>
                    <span class="ml-auto bg-amber-500/20 text-amber-400 font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">12 Pending</span>
                </a>
                <a href="#unitPerformance" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">4 Units Oversight</span>
                    <span class="ml-auto bg-slate-800 text-slate-300 font-mono-code text-[10px] px-2 py-0.5 rounded-full font-semibold">4 Units</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs">Court Evidence Reports</span>
                </a>
            </nav>
        </div>



        <!-- GOVERNANCE & COMPLIANCE -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-400 tracking-wider mb-2">Directorate Governance</div>
            <nav class="space-y-1 text-sm font-medium">
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Personnel Roster (54 Staff)</span>
                </a>
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">ISO 17025 Audit Logs</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- FORENSIC DEPARTMENT HEAD PROFILE BANNER -->
    <div class="p-3.5 bg-slate-950 border-t border-slate-800">
        <div class="flex items-center space-x-3 px-2 py-1">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-xs text-white shadow-md shadow-blue-600/30">
                KM
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-white truncate">Dr. Kwame Mensah</div>
                <div class="text-[10px] text-blue-400 font-mono-code font-bold truncate">Head of Forensic Directorate</div>
            </div>
        </div>
    </div>
</aside>