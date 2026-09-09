<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raid Operations &amp; Scene Log | Crime Scene Support</title>

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
                    <div class="w-9 h-9 rounded-lg bg-red-600 flex items-center justify-center text-white font-bold shadow-md shadow-red-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white tracking-wider text-xs font-mono-code leading-none mb-1">CRIME SCENE SUPPORT</div>
                        <div class="text-[10px] text-red-400 font-semibold uppercase">TACTICAL RAID LOG</div>
                    </div>
                </a>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-red-500/20 text-red-400 border border-red-500/30">
                    RAID
                </span>
            </div>

            <!-- NAVIGATION MENU -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div>
                    <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-400 tracking-wider mb-2">My Field Tasks</div>
                    <nav class="space-y-1 text-sm font-medium">
                        <a href="{{ url('/Crime_Scene_Support/staff') }}" class="flex items-center px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-xs">My Assigned Scenes</span>
                        </a>

                        <!-- EVENT DROPDOWN ACTIVE -->
                        <div class="relative">
                            <button type="button" onclick="toggleSidebarEventMenu()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-white bg-red-600/30 border border-red-500/40 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs font-bold">Event Pages</span>
                                </div>
                                <svg id="sidebarEventChevron" class="w-4 h-4 text-red-400 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="sidebarEventMenu" class="pl-6 pr-1 py-1 space-y-1">
                                <a href="{{ url('/Crime_Scene_Support/staff/cargo-parcel-interception') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-2"></span>
                                    Cargo/Parcel Interception
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/raid') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-bold text-white bg-red-600 rounded-lg flex items-center shadow-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white mr-2"></span>
                                    Raid
                                </a>
                                <a href="{{ url('/Crime_Scene_Support/staff/search-warrant') }}" class="w-full text-left px-3 py-1.5 text-[11px] font-medium text-slate-300 hover:text-white hover:bg-slate-800/90 rounded-lg flex items-center transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2"></span>
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
                    <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center font-bold text-xs text-white">SM</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate">Insp. Samuel Mensah</div>
                        <div class="text-[10px] text-red-400 font-mono-code font-bold truncate">Tactical Field Officer / Staff</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <input type="text" placeholder="Search Raid Target, Suspect Name, Firearms, Contraband..." class="w-full pl-4 pr-4 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-mono-code focus:outline-none focus:bg-white focus:border-red-600">
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/Crime_Scene_Support/staff/arrest-person') }}" class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all border border-slate-800">
                        <svg class="w-4 h-4 mr-1.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Arrest (Person) Page</span>
                    </a>
                    <button onclick="alert('Creating Tactical Raid Log Entry...')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-xs">
                        + Log Tactical Raid Scene
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- BANNER -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex justify-between items-center">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-red-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">EVENT PAGE</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-red-600 font-mono-code">HIGH-RISK TACTICAL STRIKE</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tactical Raid Operations Portal</h1>
                        <p class="text-xs text-slate-500 mt-1">Live Scene Logging for Joint Taskforce Raids, Suspect Arrest Dockets &amp; Weapon Seizures</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 font-mono-code font-bold text-xs border border-red-200">
                            8 Active Raid Sites
                        </span>
                        <a href="{{ url('/Crime_Scene_Support/staff/arrest-person') }}" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-mono-code font-bold text-xs shadow-2xs">
                            14 Persons Arrested &rarr;
                        </a>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200/80 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-extrabold text-slate-900 text-base">Tactical Raid Incident &amp; Suspect Arrest Register</h3>
                        <span class="text-xs text-slate-500 font-mono-code font-bold text-red-600">STRIKE TEAM ALPHA &amp; BRAVO</span>
                    </div>
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase">
                            <tr>
                                <th class="py-3.5 px-5 font-semibold">Raid Code &amp; Ref</th>
                                <th class="py-3.5 px-5 font-semibold">Target Location</th>
                                <th class="py-3.5 px-5 font-semibold">Contraband / Seized Items</th>
                                <th class="py-3.5 px-5 font-semibold">Arrested Person(s)</th>
                                <th class="py-3.5 px-5 font-semibold">Evidence Status</th>
                                <th class="py-3.5 px-5 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 font-medium" id="raidTableBody">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900 font-mono-code">RAID-2026-0419</div>
                                    <div class="text-[10px] text-slate-400">Date: 2026-09-04 04:15</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-semibold text-slate-800">East Legon Compound Sector 4</div>
                                    <div class="text-[10px] text-slate-400">Tactical Entry: Breach Authorized</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-red-700">185kg High-Purity Narcotics</div>
                                    <div class="text-[10px] text-slate-400">3 Assault Rifles &amp; Cash Vault</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900">Kofi "Jaguar" Adjei</div>
                                    <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-8840291 &bull; Male (34)</div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Scene Secured &amp; Cataloged</span>
                                </td>
                                <td class="py-4 px-5 text-right space-x-2">
                                    <a href="{{ url('/Crime_Scene_Support/staff/arrest-person') }}" class="inline-block px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-[11px] shadow-2xs">Arrest (Person) Page</a>
                                    <button onclick="alert('Viewing Raid Ballistics &amp; Drug Weight logs...')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold">Inspect Seizures</button>
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
