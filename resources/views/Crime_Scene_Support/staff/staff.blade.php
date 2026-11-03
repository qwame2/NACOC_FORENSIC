@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Crime Scene%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $dbUnitHead ? $dbUnitHead->fullname : 'N/A';
    $staffName = $authUser->fullname ?? session('user_name') ?? 'Field Staff Officer';

    $staffParts = preg_split('/\s+/', trim($staffName));
    $staffInitials = count($staffParts) >= 2 
        ? strtoupper(substr($staffParts[0], 0, 1) . substr(end($staffParts), 0, 1))
        : strtoupper(substr($staffName, 0, 2));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Scene Support Staff Workstation | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Tailwind CSS Local Asset Fallback (Warning Suppressed for clean console) -->
        <script>
          const _origWarn = console.warn;
          console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
          };
        </script>
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
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
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- SYSTEM APP CONTAINER WITH SIDEBAR & MAIN WORKSPACE -->
    <div class="flex h-screen overflow-hidden">

        <!-- LEFT CRIME SCENE SUPPORT STAFF SIDEBAR -->
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 border-r border-slate-800 select-none">
            
            <!-- BRAND / SYSTEM LOGO -->
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950">
                <a href="{{ url('/Crime_Scene_Support/staff') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold shadow-md shadow-emerald-600/30 group-hover:bg-emerald-500 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white tracking-wider text-xs font-mono-code leading-none mb-1">CRIME SCENE SUPPORT</div>
                        <div class="text-[10px] text-emerald-400 font-semibold tracking-wide uppercase">STAFF WORKSTATION</div>
                    </div>
                </a>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    STAFF
                </span>
            </div>

            <!-- NAVIGATION MENU -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                
                <!-- MY FIELD WORKSTATION -->
                <div>
                    <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-400 tracking-wider mb-2">My Field Tasks</div>
                    <nav class="space-y-1 text-sm font-medium">
                        <a href="{{ url('/Crime_Scene_Support/staff') }}" class="flex items-center px-3 py-2.5 rounded-xl text-white bg-emerald-600 shadow-sm shadow-emerald-600/30">
                            <svg class="w-5 h-5 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-xs font-bold">My Assigned Scenes</span>
                        </a>

                        <!-- EVENT DROPDOWN BUTTON -->
                        <div class="relative">
                            <button type="button" onclick="toggleSidebarEventMenu()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs font-semibold">Event</span>
                                </div>
                                <svg id="sidebarEventChevron" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <!-- DROPDOWN BUTTONS -->
                            <div id="sidebarEventMenu" class="hidden pl-6 pr-1 py-1 space-y-1">
                                <a href="{{ url('/Crime_Scene_Support/staff/cargo-parcel-interception') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    Cargo/Parcel Interception
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/raid') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    Raid
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/search-warrant') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    Search Warrant
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/intel-led-ops') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    Intel-led Ops
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/transferred-case') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    Transferred case
                                </a>
                            </div>
                        </div>

                        <a href="#exhibitsSection" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            <span class="text-xs">Evidence Log &amp; Barcodes</span>
                        </a>

                    </nav>
                </div>

                <!-- FIELD TOOLKIT -->
                <div>
                    <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-400 tracking-wider mb-2">Field Officer Tools</div>
                    <nav class="space-y-1 text-sm font-medium">
                        <a href="#" onclick="toggleEvidenceModal()" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span class="text-xs">Log New Evidence Bag</span>
                        </a>
                        <a href="#" onclick="toggleScanModal()" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span class="text-xs">Upload 3D Point Cloud</span>
                        </a>
                    </nav>
                </div>

            </div>

            <!-- STAFF OFFICER PROFILE BANNER -->
            <div class="p-3.5 bg-slate-950 border-t border-slate-800">
                <div class="flex items-center space-x-3 px-2 py-1">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-xs text-white shadow-md shadow-emerald-600/30">
                        {{ $staffInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate">{{ $staffName }}</div>
                        <div class="text-[10px] text-emerald-400 font-mono-code font-bold truncate">Field Investigator / Staff</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- RIGHT WORKSPACE MAIN EXECUTIVE PANEL -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- SYSTEM TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                
                <!-- SEARCH BAR -->
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            id="globalSearch"
                            placeholder="Staff Search: Exhibit #, Barcode, GPS Location..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOP ACTIONS & BADGES -->
                <div class="flex items-center space-x-4">
                    <!-- ACTION BUTTON -->
                    <button onclick="toggleEvidenceModal()" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-emerald-600/25 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Log Evidence Bag</span>
                    </button>
                </div>
            </header>

            <!-- MAIN WORKSPACE VIEW -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- STAFF HEADER BANNER -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">STAFF WORKSTATION</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">FIELD OFFICER DOSSIER LOGGING</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Crime Scene Staff Operations</h1>
                        <p class="text-xs text-slate-500 mt-1">Logged in Officer: <strong class="text-slate-800">{{ $staffName }}</strong> &bull; Unit Head: <strong>{{ $headName }}</strong></p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="toggleScanModal()" class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            Upload 3D Scan
                        </button>
                    </div>
                </div>

                <!-- STAFF KPIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">My Assigned Scenes</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">4 Active</div>
                            <div class="text-[11px] font-semibold text-emerald-600 mt-1">
                                <span>Assigned to {{ $staffName }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Exhibits Logged Today</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-emerald-600 font-mono-code tracking-tight">18 Bags</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>Barcoded &amp; Tamper-Sealed</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">3D Point Clouds Uploaded</span>
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-purple-700 font-mono-code tracking-tight">6 Scans</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>LiDAR &amp; Drone Photogrammetry</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Head Review Status</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">2 Pending</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>Submitted to {{ $headName }}</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- STAFF OFFICER ASSIGNED SCENES REGISTER TABLE -->
                <div id="exhibitsSection" class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                    
                    <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">My Assigned Field Scenes &amp; Evidence Workstation</h3>
                            <p class="text-xs text-slate-500">Log exhibits, upload LiDAR scans, and submit completed dossiers to Unit Head {{ $headName }}</p>
                        </div>

                        <!-- EVENT FILTER DROPDOWN BUTTON -->
                        <div class="relative">
                            <button type="button" onclick="toggleTableEventDropdown()" class="inline-flex items-center px-3.5 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-xl shadow-2xs transition-all">
                                <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span id="eventDropdownLabel">Event: All Types</span>
                                <svg class="w-3.5 h-3.5 ml-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="tableEventDropdownMenu" class="hidden absolute right-0 mt-2 w-60 bg-white rounded-xl shadow-xl border border-slate-200 z-30 py-1 font-medium">
                                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono-code border-b border-slate-100">Select Event Type</div>
                                <button type="button" onclick="selectEvent('All Events')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    All Events
                                </button>
                                <button type="button" onclick="selectEvent('Cargo/Parcel Interception')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    Cargo/Parcel Interception
                                </button>
                                <button type="button" onclick="selectEvent('Raid')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    Raid
                                </button>
                                <button type="button" onclick="selectEvent('Search Warrant')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    Search Warrant
                                </button>
                                <button type="button" onclick="selectEvent('Intel-led Ops')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    Intel-led Ops
                                </button>
                                <button type="button" onclick="selectEvent('Transferred case')" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-100 flex items-center transition-colors">
                                    Transferred case
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-5 font-semibold">Incident Ref</th>
                                    <th class="py-3.5 px-5 font-semibold">Event Type</th>
                                    <th class="py-3.5 px-5 font-semibold">Location &amp; Sector</th>
                                    <th class="py-3.5 px-5 font-semibold">Log Evidence Bag</th>
                                    <th class="py-3.5 px-5 font-semibold">3D LiDAR Status</th>
                                    <th class="py-3.5 px-5 font-semibold">Dossier Status</th>
                                    <th class="py-3.5 px-5 font-semibold text-right">Staff Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 font-medium" id="staffTableBody">
                                
                                <!-- ROW 1 -->
                                <tr class="hover:bg-slate-50/80 transition-colors" data-event="Cargo/Parcel Interception">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">NCC-2026-0912</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Assigned: 2026-09-04</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Cargo/Parcel Interception
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Tema Harbour Container Terminal 3</div>
                                        <div class="text-[11px] text-slate-500 truncate">Sealed Freight Inspection</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-emerald-700">14 Bags Sealed</div>
                                        <div class="text-[10px] text-slate-400">GPS Tagged</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-purple-700 font-bold font-mono-code">3D Cloud Uploaded</div>
                                        <div class="text-[10px] text-slate-400">0.5mm Point Resolution</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" id="staff-status-NCC-2026-0912">
                                            Awaiting Head Sign-off
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="toggleEvidenceModal()" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">+ Bag Exhibit</button>
                                        <button onclick="submitToHead('NCC-2026-0912')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Submit to Head</button>
                                    </td>
                                </tr>

                                <!-- ROW 2 -->
                                <tr class="hover:bg-slate-50/80 transition-colors" data-event="Intel-led Ops">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">NCC-2026-0914</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Assigned: 2026-09-04</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            Intel-led Ops
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Kotoka International Airport Cargo Sector C</div>
                                        <div class="text-[11px] text-slate-500 truncate">Vehicle Sweep &amp; Latent Dusting</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-emerald-700">4 Bags Sealed</div>
                                        <div class="text-[10px] text-slate-400">Barcodes Logged</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-400 font-bold font-mono-code">Pending Scan</div>
                                        <div class="text-[10px] text-slate-400">Camera Active</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" id="staff-status-NCC-2026-0914">
                                            In-Progress
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="toggleScanModal()" class="px-2.5 py-1 bg-purple-600 text-white hover:bg-purple-700 rounded-lg text-[11px] font-bold shadow-2xs">Upload 3D</button>
                                        <button onclick="submitToHead('NCC-2026-0914')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Submit to Head</button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: LOG NEW EVIDENCE BAG -->
    <div id="evidenceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Log New Evidence Bag &amp; Barcode</h3>
                <button onclick="toggleEvidenceModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submitEvidenceBag();" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Evidence Barcode #</label>
                    <input type="text" id="bagBarcode" value="BAG-2026-8890" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">Field Event Type</label>
                    <select id="bagEventType" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-medium text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
                        <option value="Cargo/Parcel Interception">Cargo/Parcel Interception</option>
                        <option value="Raid">Raid</option>
                        <option value="Search Warrant">Search Warrant</option>
                        <option value="Intel-led Ops">Intel-led Ops</option>
                        <option value="Transferred case">Transferred case</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">Item Description &amp; Condition</label>
                    <input type="text" id="bagDesc" placeholder="e.g. Latent Fingerprint Lift Card / Sealed Sample" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">GPS Coordinates</label>
                        <input type="text" id="bagGPS" value="5.6037° N, 0.1870° W" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold text-blue-700">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Logging Staff Officer</label>
                        <input type="text" value="{{ $staffName }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800">
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" onclick="toggleEvidenceModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-600/30">Seal &amp; Log Exhibit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: UPLOAD 3D POINT CLOUD -->
    <div id="scanModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Upload 3D LiDAR / Point Cloud Dataset</h3>
                <button onclick="toggleScanModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submit3DScan();" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Point Cloud File (.e57 / .LAS / .PTS)</label>
                    <input type="text" value="CSS_Scene_Terminal3_LiDAR.e57 (1.4 GB)" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold text-purple-700">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">Laser Scanner Equipment ID</label>
                    <input type="text" value="FSD-LIDAR-SCANNER-02" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" onclick="toggleScanModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 shadow-sm shadow-purple-600/30">Attach 3D Scan to Scene</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function toggleSidebarEventMenu() {
            const menu = document.getElementById('sidebarEventMenu');
            const chevron = document.getElementById('sidebarEventChevron');
            if (menu) menu.classList.toggle('hidden');
            if (chevron) chevron.classList.toggle('rotate-180');
        }

        function toggleTableEventDropdown() {
            const menu = document.getElementById('tableEventDropdownMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        function selectEvent(eventName) {
            const label = document.getElementById('eventDropdownLabel');
            if (label) {
                label.innerText = eventName === 'All Events' ? 'Event: All Types' : `Event: ${eventName}`;
            }

            const tableDropdown = document.getElementById('tableEventDropdownMenu');
            if (tableDropdown && !tableDropdown.classList.contains('hidden')) {
                tableDropdown.classList.add('hidden');
            }

            const rows = document.querySelectorAll('#staffTableBody tr');
            rows.forEach(row => {
                const rowEvent = row.getAttribute('data-event');
                if (eventName === 'All Events' || !rowEvent || rowEvent === eventName) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleEvidenceModal() {
            document.getElementById('evidenceModal').classList.toggle('hidden');
        }

        function toggleScanModal() {
            document.getElementById('scanModal').classList.toggle('hidden');
        }

        function submitToHead(ref) {
            const statusEl = document.getElementById(`staff-status-${ref}`);
            if(statusEl) {
                statusEl.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
                statusEl.innerText = 'Submitted to ' + @json($headName);
            }
            alert(`Dossier ${ref} has been submitted to Unit Head ${@json($headName)} for review and sign-off.`);
        }

        function submitEvidenceBag() {
            alert('New Evidence Bag BAG-2026-8890 successfully registered and barcoded.');
            toggleEvidenceModal();
        }

        function submit3DScan() {
            alert('3D LiDAR Point Cloud dataset attached to active scene.');
            toggleScanModal();
        }

        // Keyboard Shortcut Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
        });
    </script>
</body>
</html>
