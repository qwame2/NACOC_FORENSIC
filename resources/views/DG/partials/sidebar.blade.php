<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative overflow-x-hidden font-sans">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100 border-b border-slate-200/80">
        <a href="{{ url('/DG') }}" class="flex items-center space-x-3 group text-decoration-none">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-amber-600 font-bold tracking-wide uppercase">OFFICE OF THE DG</div>
            </div>
        </a>
    </div>

    <!-- NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        
        <!-- SUPREME COMMAND -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-amber-700 tracking-wider mb-2 flex items-center justify-between">
                <span>Supreme Command</span>
                <span class="text-[9px] text-slate-400 font-normal">Live Sync</span>
            </div>
            <nav class="space-y-1 text-xs font-medium">
                
                <!-- DASHBOARD -->
                <a href="{{ url('/DG') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('DG') || request()->is('DG/') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('DG') || request()->is('DG/') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs">Dashboard</span>
                </a>

                <!-- CASE MANAGEMENT -->
                <a href="{{ url('/DG/cases') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all text-decoration-none {{ request()->is('DG/cases*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('DG/cases*') ? 'text-white' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs flex-1">Case Register</span>
                </a>

            </nav>
        </div>

        <!-- EXECUTIVE GOVERNANCE -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-500 tracking-wider mb-2">Executive Governance</div>
            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ route('profile.settings') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors text-decoration-none {{ request()->routeIs('profile*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->routeIs('profile*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs">Profile Settings</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- DG PROFILE BANNER -->
    @php
        $authUser = auth()->user();
        if ($authUser) {
            $userName = $authUser->fullname;
            $userAvatar = $authUser->avatar;
        } else {
            $dbDG = \App\Models\User::where('is_head', true)
                ->where(function($q) {
                    $q->where('unit', 'LIKE', '%Director%')
                      ->orWhere('unit', 'LIKE', '%DG%');
                })->first() ?? \App\Models\User::where('is_head', true)->first();
            $userName = session('user_name') ?? ($dbDG ? $dbDG->fullname : 'Director-General');
            $userAvatar = $dbDG?->avatar ?? null;
        }
    @endphp
    <div class="p-3 bg-slate-200/50 border-t border-slate-200/80">
        <div class="flex items-center space-x-3 p-2 rounded-xl bg-white border border-slate-200/60 shadow-xs">
            <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center font-bold text-amber-700 text-xs shrink-0 overflow-hidden">
                @if($userAvatar)
                    <img src="{{ asset('storage/' . $userAvatar) }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($userName, 0, 2)) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-slate-900 truncate">{{ $userName }}</div>
                <div class="text-[10px] text-amber-600 font-semibold font-mono-code truncate">DIRECTOR-GENERAL (COMMAND)</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" title="Logout" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>

</aside>
