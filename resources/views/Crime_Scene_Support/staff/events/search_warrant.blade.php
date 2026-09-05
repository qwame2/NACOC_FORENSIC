<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Warrant Execution | Crime Scene Support</title>

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
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- LEFT SIDEBAR -->
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 border-r border-slate-800 select-none">
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950">
                <a href="{{ url('/Crime_Scene_Support/staff') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-lg bg-amber-600 flex items-center justify-center text-white font-bold shadow-md shadow-amber-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white tracking-wider text-xs font-mono-code leading-none mb-1">CRIME SCENE SUPPORT</div>
                        <div class="text-[10px] text-amber-400 font-semibold uppercase">SEARCH WARRANT LOG</div>
                    </div>
                </a>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    WARRANT
                </span>
            </div>

            <!-- NAVIGATION MENU -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div>
                    <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-400 tracking-wider mb-2">My Field Tasks</div>
                    <nav class="space-y-1 text-sm font-medium">
                        <a href="{{ url('/Crime_Scene_Support/staff') }}" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-xs">My Assigned Scenes</span>
                        </a>

                        <!-- EVENT DROPDOWN ACTIVE -->
                        <div class="relative">
                            <button type="button" onclick="toggleSidebarEventMenu()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-white bg-amber-600/30 border border-amber-500/40 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs font-bold">Event Pages</span>
                                </div>
                                <svg id="sidebarEventChevron" class="w-4 h-4 text-amber-400 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="sidebarEventMenu" class="pl-6 pr-1 py-1 space-y-1">
                                <a href="{{ url('/Crime_Scene_Support/staff/cargo-parcel-interception') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mr-2"></span>
                                    Cargo/Parcel Interception
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/raid') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 mr-2"></span>
                                    Raid
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/search-warrant') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-bold text-white bg-amber-600 rounded-lg flex items-center shadow-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white mr-2"></span>
                                    Search Warrant
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/intel-led-ops') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 mr-2"></span>
                                    Intel-led Ops
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/transferred-case') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-2"></span>
                                    Transferred case
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- PROFILE -->
            <div class="p-3.5 bg-slate-950 border-t border-slate-800">
                <div class="flex items-center space-x-3 px-2 py-1">
                    <div class="w-9 h-9 rounded-xl bg-amber-600 flex items-center justify-center font-bold text-xs text-white">SM</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate">Insp. Samuel Mensah</div>
                        <div class="text-[10px] text-amber-400 font-mono-code font-bold truncate">Search Warrant Lead / Staff</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <input type="text" placeholder="Search Court Warrant #, High Court Order, Premises Owner..." class="w-full pl-4 pr-4 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-mono-code focus:outline-none focus:bg-white focus:border-amber-600">
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="alert('Registering Judicial Search Warrant Execution...')" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs">
                        + Register Warrant Execution
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- BANNER -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex justify-between items-center">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-amber-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">EVENT PAGE</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-amber-600 font-mono-code">JUDICIAL &amp; PREMISES SEARCH</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Search Warrant Operations Portal</h1>
                        <p class="text-xs text-slate-500 mt-1">Court-Authorized Property Searches, Digital Storage Media Bagging &amp; Document Seizure Inventory</p>
                    </div>
                    <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 font-mono-code font-bold text-xs border border-amber-200">
                        15 Warrants Executed
                    </span>
                </div>

                <!-- TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200/80 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-extrabold text-slate-900 text-base">Court Search Warrant Execution Register</h3>
                        <span class="text-xs text-slate-500 font-mono-code">High Court of Justice / NACOC Legal Division</span>
                    </div>
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase">
                            <tr>
                                <th class="py-3.5 px-5 font-semibold">Warrant Ref &amp; Court</th>
                                <th class="py-3.5 px-5 font-semibold">Target Premises Address</th>
                                <th class="py-3.5 px-5 font-semibold">Seized Documents &amp; Media</th>
                                <th class="py-3.5 px-5 font-semibold">Chain of Custody</th>
                                <th class="py-3.5 px-5 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 font-medium">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900 font-mono-code">SW-ACC-2026-881</div>
                                    <div class="text-[10px] text-slate-400">Accra High Court Division 3</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-semibold text-slate-800">Airport Residential Area Villa 12</div>
                                    <div class="text-[10px] text-slate-400">Owner: Corporate Entity X</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-amber-700">12 Encrypted Hard Drives</div>
                                    <div class="text-[10px] text-slate-400">Financial Ledger Binders</div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Tamper-Evident Bagged</span>
                                </td>
                                <td class="py-4 px-5 text-right space-x-2">
                                    <button onclick="alert('Viewing Warrant inventory manifests...')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold">View Manifest</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebarEventMenu() {
            const menu = document.getElementById('sidebarEventMenu');
            if (menu) menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>
