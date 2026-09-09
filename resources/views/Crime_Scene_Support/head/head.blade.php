@php
    $authUser = auth()->user();
    $headName = $authUser->fullname ?? session('user_name') ?? 'Adomako Emmanuel';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Scene Support Unit Head Command | NACOC Forensic</title>

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

        <!-- LEFT CRIME SCENE SUPPORT UNIT SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

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
                            placeholder="Head Search: Field Incident ID, Officer Name, GPS Coordinates..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOP ACTIONS & BADGES -->
                <div class="flex items-center space-x-4">


                    <!-- ACTION BUTTON -->
                    <button onclick="toggleDispatchModal()" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-emerald-600/25 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Dispatch Field Team</span>
                    </button>
                </div>
            </header>

            <!-- MAIN WORKSPACE VIEW -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- UNIT HEAD HEADER BANNER -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-100 p-6 rounded-2xl border-none shadow-xl">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Crime Scene Support Unit Head Portal</h1>
                        <p class="text-xs text-slate-500 mt-1">Commanding Officer: <strong class="text-slate-800">{{ $headName }}</strong> &bull; 14 Field Investigators Reporting</p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="alert('Exporting Unit Head Field Summary PDF...')" class="inline-flex items-center px-3.5 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all">
                            <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Head Report
                        </button>
                    </div>
                </div>

                <!-- HEAD KPIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <div class="bg-slate-100 p-5 rounded-2xl border-none shadow-xl hover:shadow-2xl transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Active Field Operations</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-100/70 text-emerald-700 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">142 Scenes</div>
                            <div class="text-[11px] font-semibold text-emerald-600 mt-1">
                                <span>6 Teams Currently Dispatched</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-100 p-5 rounded-2xl border-none shadow-xl hover:shadow-2xl transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Pending Head Sign-offs</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-100/70 text-amber-700 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-amber-600 font-mono-code tracking-tight">8 Dossiers</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>Awaiting {{ $headName }} Approval</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-100 p-5 rounded-2xl border-none shadow-xl hover:shadow-2xl transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">3D Point Cloud Datasets</span>
                            <div class="w-8 h-8 rounded-lg bg-purple-100/70 text-purple-700 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-purple-700 font-mono-code tracking-tight">38 Scans</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>LiDAR &amp; Drone Surveys</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-100 p-5 rounded-2xl border-none shadow-xl hover:shadow-2xl transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Field Officers Active</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-100/70 text-emerald-700 flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">14 Officers</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>Ready &amp; Dispatched</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- UNIT HEAD MASTER DISPATCH REGISTER TABLE -->
                <div id="sceneRegister" class="bg-slate-100 rounded-2xl border-none shadow-xl overflow-hidden">
                    
                    <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Unit Head Scene Approvals &amp; Dispatch Oversight</h3>
                            <p class="text-xs text-slate-500">Review field officer submissions, authorize 3D point cloud reports, and initiate evidence transfers</p>
                        </div>
                    </div>

                    <!-- DATA TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-5 font-semibold">Incident Ref</th>
                                    <th class="py-3.5 px-5 font-semibold">Scene Location &amp; Summary</th>
                                    <th class="py-3.5 px-5 font-semibold">Field Officer In-Charge</th>
                                    <th class="py-3.5 px-5 font-semibold">3D Scan Dataset</th>
                                    <th class="py-3.5 px-5 font-semibold">Head Approval Status</th>
                                    <th class="py-3.5 px-5 font-semibold text-right">Head Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 font-medium" id="headTableBody">
                                
                                <!-- ROW 1 -->
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">CSS-2026-0912</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-04 08:30</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Tema Harbour Container Terminal 3</div>
                                        <div class="text-[11px] text-slate-500 truncate">Latent Fingerprint &amp; Container Seal Sweep</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-800">Insp. Samuel Mensah</div>
                                        <div class="text-[10px] text-slate-400">Senior Field Lead</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-purple-700 font-bold font-mono-code">LiDAR-3D #0912</div>
                                        <div class="text-[10px] text-slate-400">14 Exhibits Bagged</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" id="status-CSS-2026-0912">
                                            Awaiting Head Approval
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewHeadDetails('CSS-2026-0912')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect 3D</button>
                                        <button onclick="approveHeadDossier('CSS-2026-0912')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Approve Dossier</button>
                                    </td>
                                </tr>

                                <!-- ROW 2 -->
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">CSS-2026-0908</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-03 14:15</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Kotoka Int. Airport Cargo Facility Sector B</div>
                                        <div class="text-[11px] text-slate-500 truncate">High-Value Concealment Incident</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-800">Sgt. Abena Osei</div>
                                        <div class="text-[10px] text-slate-400">Field Officer</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-purple-700 font-bold font-mono-code">Drone-3D #0908</div>
                                        <div class="text-[10px] text-slate-400">8 Exhibits Bagged</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Approved by {{ $headName }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewHeadDetails('CSS-2026-0908')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect 3D</button>
                                        <button onclick="alert('Transferring Exhibits to Evidence Unit...')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Transfer Unit</button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: DISPATCH FIELD TEAM -->
    <div id="dispatchModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Dispatch Crime Scene Field Team</h3>
                <button onclick="toggleDispatchModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submitHeadDispatch();" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Incident Reference Number</label>
                    <input type="text" id="headDispatchRef" value="CSS-2026-0920" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">Incident Location &amp; Sector</label>
                    <input type="text" id="headDispatchLoc" placeholder="e.g. Tema Port Terminal 2 / Aflao Border" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Assigned Field Investigator</label>
                        <select id="headDispatchOfficer" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-medium">
                            <option>Insp. Samuel Mensah</option>
                            <option>Sgt. Abena Osei</option>
                            <option>Det. Eric Boateng</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Authorizing Unit Head</label>
                        <input type="text" value="{{ $headName }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-blue-700">
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" onclick="toggleDispatchModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-600/30">Issue Field Authorization</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: 3D DOSSIER INSPECTION -->
    <div id="headDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <div class="text-[10px] font-mono-code font-bold text-purple-600 uppercase">3D LiDAR &amp; Point Cloud Dossier</div>
                    <h3 class="text-base font-extrabold text-slate-900" id="modalHeadTitle">CSS-2026-0912</h3>
                </div>
                <button onclick="toggleHeadDetailsModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono-code text-xs space-y-2.5">
                <div class="flex justify-between"><span class="text-slate-500">GPS COORDINATES:</span> <span class="font-bold text-blue-600">5.6037&deg; N, 0.1870&deg; W</span></div>
                <div class="flex justify-between"><span class="text-slate-500">3D SCAN RESOLUTION:</span> <span class="text-purple-700 font-bold">0.5mm Point Cloud Data</span></div>
                <div class="flex justify-between"><span class="text-slate-500">LOGGED EXHIBITS:</span> <span class="text-emerald-600 font-bold">14 Sealed Evidence Bags</span></div>
                <div class="flex justify-between"><span class="text-slate-500">UNIT HEAD:</span> <span class="text-slate-800">{{ $headName }}</span></div>
            </div>

            <div class="pt-2 flex justify-end">
                <button onclick="toggleHeadDetailsModal()" class="px-4 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs">Close Inspection</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function toggleDispatchModal() {
            document.getElementById('dispatchModal').classList.toggle('hidden');
        }

        function toggleHeadDetailsModal() {
            document.getElementById('headDetailsModal').classList.toggle('hidden');
        }

        function viewHeadDetails(ref) {
            document.getElementById('modalHeadTitle').innerText = ref;
            toggleHeadDetailsModal();
        }

        function approveHeadDossier(ref) {
            const statusEl = document.getElementById(`status-${ref}`);
            if(statusEl) {
                statusEl.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                statusEl.innerText = 'Approved by ' + @json($headName);
            }
            alert(`Field Incident Dossier ${ref} has been officially approved by Unit Head ${@json($headName)}.`);
        }

        function submitHeadDispatch() {
            const ref = document.getElementById('headDispatchRef').value;
            const loc = document.getElementById('headDispatchLoc').value;
            const officer = document.getElementById('headDispatchOfficer').value;

            const tableBody = document.getElementById('headTableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-slate-50/80 transition-colors';
            newRow.innerHTML = `
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900 font-mono-code text-sm">${ref}</div>
                    <div class="text-[10px] text-slate-400 font-mono-code">Dispatched: ${new Date().toISOString().slice(0, 10)}</div>
                </td>
                <td class="py-4 px-5 max-w-xs">
                    <div class="font-semibold text-slate-800">${loc}</div>
                    <div class="text-[11px] text-slate-500 truncate">Unit Head Authorized Dispatch</div>
                </td>
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-800">${officer}</div>
                    <div class="text-[10px] text-slate-400">Assigned Investigator</div>
                </td>
                <td class="py-4 px-5">
                    <div class="text-purple-700 font-bold font-mono-code">3D LiDAR In-Progress</div>
                    <div class="text-[10px] text-slate-400">Field Scanning</div>
                </td>
                <td class="py-4 px-5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Dispatch Active
                    </span>
                </td>
                <td class="py-4 px-5 text-right space-x-2">
                    <button onclick="viewHeadDetails('${ref}')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect 3D</button>
                    <button onclick="approveHeadDossier('${ref}')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Approve Dossier</button>
                </td>
            `;

            tableBody.insertBefore(newRow, tableBody.firstChild);
            toggleDispatchModal();
        }

        // Keyboard Shortcut Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
        });

        /* ── 3-Second Real-Time Polling Interval ─────── */
        async function pollUnitHeadCasesTable() {
            try {
                const dispatchModal = document.getElementById('dispatchModal');
                const headDetailsModal = document.getElementById('headDetailsModal');
                if ((dispatchModal && !dispatchModal.classList.contains('hidden')) ||
                    (headDetailsModal && !headDetailsModal.classList.contains('hidden'))) {
                    return;
                }

                const res = await fetch('/api/cases', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const cases = await res.json();
                if (!Array.isArray(cases)) return;

                const tbody = document.getElementById('headTableBody');
                if (!tbody) return;

                const searchQuery = (document.getElementById('globalSearch')?.value || '').toLowerCase().trim();

                let html = '';
                if (cases.length === 0) {
                    html = `<tr><td colspan="6" class="py-8 text-center text-slate-500 font-mono-code text-xs">No unit cases found in database.</td></tr>`;
                } else {
                    cases.forEach(c => {
                        const createdDate = c.created_at ? c.created_at.slice(0, 16).replace('T', ' ') : new Date().toISOString().slice(0, 16).replace('T', ' ');
                        const isApproved = ['Approved', 'Authorized', 'Court Ready', 'Completed'].includes(c.status);

                        let statusBadge = isApproved
                            ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Approved by Unit Head</span>`
                            : `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" id="status-${c.case_number}">Awaiting Head Approval</span>`;

                        let actionBtns = isApproved
                            ? `<button onclick="viewHeadDetails('${c.case_number}')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect 3D</button> <a href="/cases/${c.case_number}/review" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs inline-block">Review Dossier</a>`
                            : `<button onclick="viewHeadDetails('${c.case_number}')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect 3D</button> <button onclick="approveHeadDossier('${c.case_number}')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Approve Dossier</button>`;

                        const rowText = `${c.case_number} ${c.title} ${c.location} ${c.submitting_officer}`.toLowerCase();
                        const matchesSearch = !searchQuery || rowText.includes(searchQuery);

                        html += `<tr class="hover:bg-slate-50/80 transition-colors" style="${matchesSearch ? '' : 'display:none;'}">
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 font-mono-code text-sm">${c.case_number}</div>
                                <div class="text-[10px] text-slate-400 font-mono-code">Submitted: ${createdDate}</div>
                            </td>
                            <td class="py-4 px-5 max-w-xs">
                                <div class="font-semibold text-slate-800">${c.location || c.raid_location || 'Theme Harbour Terminal'}</div>
                                <div class="text-[11px] text-slate-500 truncate">${c.title || 'Field Incident Record'}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-800">${c.submitting_officer || c.commanding_officer || 'Field Lead'}</div>
                                <div class="text-[10px] text-slate-400">Senior Field Lead</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-purple-700 font-bold font-mono-code">LiDAR-3D #${c.case_number.slice(-4)}</div>
                                <div class="text-[10px] text-slate-400">${c.exhibits_summary || 'Exhibits Bagged'}</div>
                            </td>
                            <td class="py-4 px-5">${statusBadge}</td>
                            <td class="py-4 px-5 text-right space-x-2">${actionBtns}</td>
                        </tr>`;
                    });
                }

                tbody.innerHTML = html;
            } catch (e) {
                console.warn('Unit head polling failed:', e);
            }
        }
        setInterval(pollUnitHeadCasesTable, 3000);
    </script>
</body>
</html>
