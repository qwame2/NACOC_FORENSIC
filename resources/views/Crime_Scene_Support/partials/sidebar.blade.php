<!-- LEFT CRIME SCENE SUPPORT UNIT SIDEBAR (EXECUTIVE GRAY THEME WITH SHADOW) -->
<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100">
        <a href="{{ url('/Crime_Scene_Support/head') }}" class="flex items-center space-x-3 group">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC LOGO.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-emerald-600 font-bold tracking-wide uppercase">CRIME SCENE UNIT</div>
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
                
                <!-- CASE DIRECTORY -->
                <a href="{{ url('/cases') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('cases*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('cases*') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Case Directory</span>
                </a>

                <!-- HEAD OVERVIEW -->
                <a href="{{ url('/Crime_Scene_Support/head') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs">Head Overview</span>
                </a>

                
                
            </nav>
        </div>

        <!-- EVENT DOCKETS -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-600 tracking-wider mb-2">Event Dockets</div>
            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ url('/arrest-person') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('arrest-person*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('arrest-person*') ? 'text-white' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span class="truncate">Arrest &amp; Seizure (New Case)</span>
                </a>
                <a href="{{ url('/cargo-parcel-interception') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('cargo-parcel-interception*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('cargo-parcel-interception*') ? 'text-white' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="truncate">Cargo &amp; Parcel Interception</span>
                </a>
                <!--<a href="#" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('raid*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('raid*') ? 'text-white' : 'text-rose-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="truncate">Raid Operation</span>
                </a>-->
                <a href="{{ url('/search-warrant') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('search-warrant*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('search-warrant*') ? 'text-white' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="truncate">Search Warrant Executed</span>
                </a>
                <a href="{{ url('/intel-led-ops') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('intel-led-ops*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('intel-led-ops*') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="truncate">Intel-Led Operation</span>
                </a>
                <a href="{{ url('/transferred-case') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('transferred-case*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('transferred-case*') ? 'text-white' : 'text-cyan-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span class="truncate">Transferred Case</span>
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
                    <span class="text-xs">LiDAR &amp; Drone Equipment</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- UNIT HEAD PROFILE BANNER -->
    <div class="p-3.5 bg-slate-200/50 space-y-2">
        <div class="flex items-center space-x-3 px-2 py-1">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-extrabold text-xs text-white shadow-sm shadow-emerald-600/30 shrink-0">
                {{ Auth::check() ? strtoupper(substr(Auth::user()->fullname, 0, 2)) : 'HD' }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-extrabold text-slate-900 truncate">{{ Auth::check() ? Auth::user()->fullname : 'Unit Head Officer' }}</div>
                <div class="text-[10px] text-blue-600 font-mono-code font-bold truncate">{{ Auth::check() ? Auth::user()->unit : 'Crime Scene Support' }} Head</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-white text-slate-800 hover:bg-red-600 hover:text-white text-xs font-bold transition-all shadow-sm cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
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
                        window.location.href = '/cases';
                    } else {
                        window.location.href = '/Crime_Scene_Support/head';
                    }
                }
            }, true);
        });
    })();
</script>
