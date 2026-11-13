<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative overflow-x-hidden font-sans">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100 border-b border-slate-200/80">
        <a href="{{ url('/Forensic_Head') }}" class="flex items-center space-x-3 group text-decoration-none">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-blue-600 font-bold tracking-wide uppercase">DEPT HEAD PORTAL</div>
            </div>
        </a>
    </div>

    <!-- NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        
        <!-- DIRECTORATE COMMAND -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-blue-600 tracking-wider mb-2 flex items-center justify-between">
                <span>Directorate Command</span>
                <span class="text-[9px] text-slate-400 font-normal">3s Sync</span>
            </div>
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
                </a>

                <!-- COURT EVIDENCE REPORTS / SITREP -->
                <a href="{{ url('/court-evidence') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('court-evidence*') || request()->is('Forensic_Head/court-evidence*') || request()->is('court-evidence-sitrep*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('court-evidence*') || request()->is('Forensic_Head/court-evidence*') || request()->is('court-evidence-sitrep*') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs flex-1">Court Evidence &amp; SITREP</span>
                </a>

            </nav>
        </div>

        <!-- DIRECTORATE GOVERNANCE -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-500 tracking-wider mb-2">Directorate Governance</div>
            <nav class="space-y-1 text-xs font-semibold">
                @php
                    $fhNotificationsCount = \App\Http\Controllers\NotificationsController::countActiveForContext('Forensic Directorate', auth()->user(), 'forensic_head');
                    $isFhNotificationsActive = request()->is('Forensic_Head/notifications*');
                @endphp
                <a href="{{ url('/Forensic_Head/notifications') }}" class="flex items-center px-3 py-2 rounded-xl {{ $isFhNotificationsActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }} transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isFhNotificationsActive ? 'text-white' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="text-xs flex-1">Notifications</span>
                    <span id="sidebarNotificationsBadge" class="ml-auto {{ $isFhNotificationsActive ? 'bg-white/20 text-white' : ($fhNotificationsCount > 0 ? 'bg-amber-500/15 text-amber-800 border border-amber-300/60' : 'bg-slate-200 text-slate-600') }} font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold">
                        {{ $fhNotificationsCount }}
                    </span>
                </a>
                <a href="{{ url('/Forensic_Head/personnel-roster') }}" class="flex items-center px-3 py-2 rounded-xl {{ request()->is('Forensic_Head/personnel-roster*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }} transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Forensic_Head/personnel-roster*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Personnel Details</span>
                </a>
                <a href="{{ url('/Forensic_Head/permissions') }}" class="flex items-center px-3 py-2 rounded-xl {{ request()->is('*permissions*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }} transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*permissions*') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-xs">Permissions</span>
                </a>
                <a href="{{ url('/Forensic_Head/staff-audit-logs') }}" class="flex items-center px-3 py-2 rounded-xl {{ request()->is('*staff-audit-logs*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }} transition-colors text-decoration-none">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*staff-audit-logs*') ? 'text-white' : 'text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Staff Audit Logs</span>
                </a>
                <a href="{{ route('profile.settings') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors text-decoration-none {{ request()->routeIs('profile*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->routeIs('profile*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs">Profile Settings</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- DEPT HEAD PROFILE BANNER -->
    @php
        $authUser = auth()->user();
        if ($authUser) {
            $userName = $authUser->fullname;
            $userAvatar = $authUser->avatar;
        } else {
            $dbForensicHead = \App\Models\User::where('is_head', true)
                ->where(function($q) {
                    $q->where('unit', 'LIKE', '%Forensic Directorate%')
                      ->orWhere('unit', 'LIKE', '%Forensic Head%')
                      ->orWhere('unit', 'LIKE', '%Directorate Head%');
                })->first() ?? \App\Models\User::where('is_head', true)->first();
            $userName = session('user_name') ?? ($dbForensicHead ? $dbForensicHead->fullname : 'N/A');
            $userAvatar = $dbForensicHead?->avatar ?? null;
        }

        $nameParts = preg_split('/\s+/', trim($userName));
        $initials = count($nameParts) >= 2 
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($userName, 0, 2));
        $userRole = ($authUser && $authUser->is_head) ? 'Forensic Head - NACOC' : 'Commanding Officer - NACOC';
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
                <div class="text-[11px] text-emerald-700 font-extrabold truncate" title="{{ $userRole }}">{{ $userRole }}</div>
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

<!-- SIDEBAR NAVIGATION HELPERS -->
<script>
    function filterDeptHeadUnit(unit, btn) {
        if (typeof filterUnit === 'function') {
            const target = (unit === 'all' || unit === 'ALL') ? 'ALL' : (unit === 'Digital' ? 'Digital & Physical Forensics' : unit);
            const unitBtn = Array.from(document.querySelectorAll('.unit-tab')).find(b => b.innerText.toLowerCase().includes(unit.toLowerCase())) || document.querySelector('.unit-tab');
            filterUnit(target, unitBtn);
        } else {
            const u = (unit === 'all' || unit === 'ALL') ? '' : (unit === 'Digital' ? 'Digital & Physical Forensics' : unit);
            window.location.href = '/Forensic_Head/cases' + (u ? '?unit=' + encodeURIComponent(u) : '');
        }
    }
    window.filterDeptHeadUnit = filterDeptHeadUnit;
</script>