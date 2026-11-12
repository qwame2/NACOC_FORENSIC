@php
    $authUser = auth()->user();
    $userUnit = !empty($authUser->unit) ? $authUser->unit : 'Evidence Management';
    $sidebarUnit = 'Evidence Management';
    $officerName = $authUser->fullname ?? session('user_name') ?? 'Evidence Custodian';
    $todayDate = date('Y-m-d');
    $currentTime = date('H:i');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Seized Exhibit | Section 4: Seizures (Commodities) | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <!-- jQuery & QRCode JS Library -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased select-none">

    <div class="flex h-screen overflow-hidden">

        <!-- GENERAL SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden select-text">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                            placeholder="Search Vault Exhibits, Commodity Name, Property ID..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-inner">
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/Evidence_Management/vault') }}"
                        class="px-4 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
                        <span>&larr;</span>
                        <span>Back to Exhibit Vault</span>
                    </a>
                    <div class="h-6 w-px bg-slate-200"></div>
                    <div class="flex items-center space-x-2 pl-1">
                        <div class="w-8 h-8 rounded-full bg-emerald-700 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr($officerName, 0, 2)) }}
                        </div>
                        <span class="text-xs font-bold text-slate-700 hidden md:inline truncate max-w-[140px]">{{ $officerName }}</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">

                <!-- BANNER -->
                <div class="bg-slate-100 p-6 rounded-2xl border-none shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">
                                EVIDENCE MANAGEMENT UNIT
                            </span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">PROPERTY &amp; CUSTODY COMMAND</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Section 4: Seizures (Commodities)</h1>
                        <p class="text-xs text-slate-500 mt-1">Log seized exhibit(s) without a suspect into the Exhibit Vault. If a suspect is arrested later, their biodata can be linked to this exhibit from any unit's Create Case page.</p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-mono-code font-bold rounded-xl border border-emerald-200 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Direct Vault Lodgement
                        </span>
                    </div>
                </div>

                <!-- HIDDEN SECTIONS 1 TO 3 WRAPPER (Required for workflow test parity) -->
                <div id="sections1To3Wrapper" class="hidden"></div>

                <!-- ADD EXHIBIT DOCKET FORM -->
                <form id="addExhibitForm" autocomplete="off" onsubmit="event.preventDefault(); processAddExhibit();" novalidate class="space-y-6 text-xs font-medium">
                    @csrf

                    <!-- SECTION 4: SEIZURES (COMMODITIES) -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6 relative z-20 overflow-hidden">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 4: Seizures (Commodities)</h3>
                                    <p class="text-xs text-slate-500">Record illicit substance types, Weight/quantity, street valuation, and evidence vault allocation</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200/80 uppercase tracking-wider shadow-2xs">
                                    Contraband Inventory
                                </span>
                            </div>
                        </div>

                        <!-- SEIZURES DETAILS CONTAINER -->
                        <div id="seizuresDetailsContainer" class="p-5 bg-emerald-50/40 rounded-2xl border border-emerald-200/80 space-y-5 shadow-2xs overflow-hidden">

                            <div class="flex items-center justify-between border-b border-slate-300/60 pb-3 flex-wrap gap-2">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center text-xs font-bold shadow-sm shadow-slate-200/50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">
                                            Seized Commodities &amp; Contraband Manifest
                                        </h4>
                                        <p class="text-[11px] text-slate-500">Log exhibits directly into vault custody without requiring an immediate suspect</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2.5 flex-wrap">
                                    <div class="inline-flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-300 shadow-2xs">
                                        <label for="seizureCountInput" class="text-[11px] font-extrabold uppercase font-mono-code text-slate-700 whitespace-nowrap">
                                            Number of Seized Items:
                                        </label>
                                        <input type="number" id="seizureCountInput" min="1" max="50" value="1"
                                            onchange="setSeizureBlocksCount(this.value)"
                                            oninput="setSeizureBlocksCount(this.value)"
                                            class="w-16 px-2 py-0.5 text-center font-mono-code font-black text-xs text-slate-900 bg-emerald-50 border border-emerald-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all">
                                    </div>
                                    <span class="px-2.5 py-1 bg-emerald-100 text-black text-[10px] font-mono-code font-bold rounded-lg border border-slate-300 uppercase">
                                        Evidence Tagging Active
                                    </span>
                                    <button type="button" onclick="addSeizureBlock()"
                                        class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        Add Seizure
                                    </button>
                                </div>
                            </div>

                            <!-- SEIZURES LIST CONTAINER -->
                            <div id="seizuresListContainer" class="space-y-6">

                                <!-- SEIZURE BLOCK #1 -->
                                <div class="seizure-block bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-5 relative" data-seizure-index="1">

                                    <!-- SEIZURE ITEM HEADER -->
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center seizure-number-badge">1</span>
                                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider seizure-title">Seized Item 1</h5>
                                            <span class="property-id-badge text-[10px] font-mono-code font-extrabold px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-lg border border-slate-200">
                                                ID: NACOC/EMU/-----
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2 seizure-header-right">
                                            <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">
                                                UNASSIGNED EXHIBIT
                                            </span>
                                            <button type="button" onclick="removeSeizureBlock(this)"
                                                class="remove-seizure-btn hidden text-red-600 hover:bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center gap-1"
                                                title="Remove Seized Item">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Remove Item
                                            </button>
                                        </div>
                                    </div>

                                    <!-- SEIZURES INPUT GRID -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                                        <!-- CUSTODY / OWNERSHIP (UNASSIGNED) -->
                                        <div class="relative z-30">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Custody / Ownership Status
                                            </label>
                                            <div class="px-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl font-bold text-slate-700 text-xs flex items-center justify-between">
                                                <span>Unassigned (Vault Lodgement)</span>
                                                <span class="text-[9px] font-mono-code font-bold px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded">NO SUSPECT</span>
                                            </div>
                                            <input type="hidden" class="seizure-associated-person-select" value="All Suspects">
                                        </div>

                                        <!-- EXHIBIT TYPE DROPDOWN -->
                                        <div class="relative z-30">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit Type
                                            </label>
                                            <div class="relative">
                                                <select class="exhibit-type-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                                                    <option value="">-- Select Type --</option>
                                                    <option value="Drug Exhibit">Drug Exhibit</option>
                                                    <option value="Non-Drug Exhibit">Non-Drug Exhibit</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- DRUG CLASSIFICATION DROPDOWN -->
                                        <div class="relative z-30">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Classify Drugs
                                            </label>
                                            <div class="relative">
                                                <select class="drug-classification-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                                                    <option value="N/A">N/A</option>
                                                    <option value="Class A">Class A</option>
                                                    <option value="Class B">Class B</option>
                                                    <option value="Class C">Class C</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- EXHIBIT (COMMODITY CATEGORY) - SEARCHABLE DROPDOWN -->
                                        <div class="relative z-[9999]" style="z-index: 9999;">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <div id="exhibitSelectWrapper" class="relative z-[9999]" style="z-index: 9999;">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                    </span>
                                                    <input type="text" id="exhibitSearchInput"
                                                        placeholder="Select or type exhibit category..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openExhibitListPanel(this)"
                                                        oninput="filterExhibitList(this)"
                                                        onfocus="openExhibitListPanel(this)">
                                                    <input type="hidden" id="newSeizureCategory" value="">
                                                </div>
                                                <div id="exhibitListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] overflow-hidden"
                                                    style="z-index: 9999; max-height: 280px; overflow-y: auto;">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="exhibitSearchInner"
                                                            placeholder="Type to filter categories..."
                                                            oninput="filterExhibitListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Drugs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Drugs</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-black px-2 py-0.5 rounded font-bold">DRG</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Money', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Money</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Cash, Foreign Currencies, Laundering</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">CASH</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Precious Stones', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Precious Stones</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Diamonds, Gemstones, Gold, Bullion</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold">MET</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Vehicles', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Vehicles</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Automobiles, Vessels, Trucks</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">VEH</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Electronic Devices', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Electronic Devices</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Phones, Encrypted Laptops, Drives</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-bold">ELEC</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Weapons', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Weapons</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Firearms, Handguns, Blades</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">WPN</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Ammunition', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Ammunition</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Bullets, Cartridges, Explosives</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-2 py-0.5 rounded font-bold">AMMO</span>
                                                            </button>
                                                        </li>
                                                        <li class="exhibit-item" onclick="selectExhibitCategory('Other Exhibits', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Other Exhibits</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Hardware, Miscellaneous Contraband</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">MISC</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                        <div id="commodityFieldWrapper" class="col-span-full md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Primary Commodity Name <span class="text-red-600 font-bold">*</span>
                                            </label>

                                            <!-- TEXT INPUT (shown for non-drug categories) -->
                                            <div id="commodityTextWrapper" class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                                </span>
                                                <input type="text" id="newSeizureName"
                                                    placeholder="e.g. Suspected Cocaine (High Purity Slabs)"
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>

                                            <!-- DRUG SELECT DROPDOWN -->
                                            <div id="drugSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                    </span>
                                                    <input type="text" id="drugSearchInput"
                                                        placeholder="Select or type custom drug name..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openDrugListPanel(this)"
                                                        oninput="filterDrugList(this)"
                                                        onfocus="openDrugListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameDrug" value="">
                                                </div>
                                                <div id="drugListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height: 240px; overflow-y: auto;">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="drugSearchInner"
                                                            placeholder="Type to filter drugs..."
                                                            oninput="filterDrugListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Opioids &amp; Analgesics</li>
                                                        <li class="drug-item" onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Heroin (Brown Sugar / Diacetylmorphine)</span>
                                                                <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item" onclick="selectDrugType('Fentanyl (Synthetic Opioid Powder)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Fentanyl (Synthetic Opioid Powder)</span>
                                                                <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item" onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Tramadol (High-Dose Tablets / Capsules)</span>
                                                                <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Stimulants &amp; Cocaine-Class</li>
                                                        <li class="drug-item" onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cocaine (High-Purity Powder / Slabs)</span>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item" onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Methamphetamine (Crystal Meth / Ice)</span>
                                                                <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                            </button>
                                                        </li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Cannabis &amp; Synthetic Cannabinoids</li>
                                                        <li class="drug-item" onclick="selectDrugType('Cannabis (Herbal / Marijuana compressed)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cannabis (Herbal / Marijuana compressed)</span>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item" onclick="selectDrugType('Hashish (Cannabis Resin / Slabs)', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Hashish (Cannabis Resin / Slabs)</span>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- WEAPON SELECT DROPDOWN -->
                                            <div id="weaponSelectWrapper" class="hidden relative z-40">
                                                <input type="text" id="weaponSearchInput"
                                                    placeholder="Select or type weapon name..."
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                <input type="hidden" id="newSeizureNameWeapon" value="">
                                            </div>

                                            <!-- CURRENCY SELECT DROPDOWN -->
                                            <div id="currencySelectWrapper" class="hidden relative z-40">
                                                <input type="text" id="currencySearchInput"
                                                    placeholder="e.g. Ghana Cedis (GHS), US Dollars (USD), Euros (EUR)..."
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                <input type="hidden" id="newSeizureNameCurrency" value="">
                                            </div>

                                            <!-- AMMO SELECT DROPDOWN -->
                                            <div id="ammoSelectWrapper" class="hidden relative z-40">
                                                <input type="text" id="ammoSearchInput"
                                                    placeholder="e.g. 9mm Luger, 7.62x39mm AK rounds, 5.56 NATO..."
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                <input type="hidden" id="newSeizureNameAmmo" value="">
                                            </div>

                                            <!-- DEVICE SELECT DROPDOWN -->
                                            <div id="deviceSelectWrapper" class="hidden relative z-40">
                                                <input type="text" id="deviceSearchInput"
                                                    placeholder="e.g. Apple iPhone 15 Pro, Samsung Galaxy S24, HP Laptop..."
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                <input type="hidden" id="newSeizureNameDevice" value="">
                                            </div>
                                        </div>

                                        <!-- QUANTITY -->
                                        <div>
                                            <label id="quantityLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Quantity / Count
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                                </span>
                                                <input type="text" id="newSeizureQuantity" placeholder="e.g. 10 Slabs, 5 Bags"
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- PACKAGING TYPE -->
                                        <div class="relative z-20">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Packaging Type
                                            </label>
                                            <div id="pkgSelectWrapper" class="relative">
                                                <input type="text" id="pkgSearchInput"
                                                    placeholder="Select or type packaging..."
                                                    autocomplete="off"
                                                    class="w-full pl-3 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                    onclick="openPkgListPanel(this)"
                                                    oninput="filterPackagingList(this)"
                                                    onfocus="openPkgListPanel(this)">
                                                <input type="hidden" id="newSeizurePackaging" value="">
                                                <div id="pkgListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height: 220px; overflow-y: auto;">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="pkgSearchInner"
                                                            placeholder="Type to filter packaging..."
                                                            oninput="filterPackagingListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="pkgList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="pkg-item" onclick="selectPackagingType('Slabs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button>
                                                        </li>
                                                        <li class="pkg-item" onclick="selectPackagingType('Brown Taped Slabs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Brown Taped Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button>
                                                        </li>
                                                        <li class="pkg-item" onclick="selectPackagingType('Zip-lock Bags', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Zip-lock Bags</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BAG</span></button>
                                                        </li>
                                                        <li class="pkg-item" onclick="selectPackagingType('Jute Sacks / Bales', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Jute Sacks / Bales</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SCK</span></button>
                                                        </li>
                                                        <li class="pkg-item" onclick="selectPackagingType('Cardboard Boxes', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Cardboard Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BOX</span></button>
                                                        </li>
                                                        <li class="pkg-item" onclick="selectPackagingType('Parcels', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Parcels</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCL</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- WEIGHT -->
                                        <div>
                                            <label id="weightLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Weight
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">⚖</span>
                                                <input type="number" step="0.01" id="newSeizureWeight" placeholder="e.g. 12.50"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- MEASUREMENT UNIT -->
                                        <div class="relative z-20">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Measurement Unit
                                            </label>
                                            <div id="unitSelectWrapper" class="relative">
                                                <input type="text" id="unitSearchInput"
                                                    placeholder="Select or type unit..."
                                                    autocomplete="off"
                                                    class="w-full pl-3 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                    onclick="openUnitListPanel(this)"
                                                    oninput="filterUnitList(this)"
                                                    onfocus="openUnitListPanel(this)">
                                                <input type="hidden" id="newSeizureUnit" value="">
                                                <div id="unitListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height: 220px; overflow-y: auto;">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="unitSearchInner"
                                                            placeholder="Type to filter units..."
                                                            oninput="filterUnitListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="unitList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Weight &amp; Mass</li>
                                                        <li class="unit-item" onclick="selectUnit('kg', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Kilograms</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button>
                                                        </li>
                                                        <li class="unit-item" onclick="selectUnit('g', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Grams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button>
                                                        </li>
                                                        <li class="unit-item" onclick="selectUnit('lbs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Pounds</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">lbs</span></button>
                                                        </li>
                                                        <li class="unit-item" onclick="selectUnit('tonnes', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Tonnes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">t</span></button>
                                                        </li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Volume &amp; Units</li>
                                                        <li class="unit-item" onclick="selectUnit('L', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Litres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button>
                                                        </li>
                                                        <li class="unit-item" onclick="selectUnit('pcs', event)">
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Pieces</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">pcs</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- VALUE (GHS) -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Est. Value (GH₵)
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">GH₵</span>
                                                <input type="text" id="newSeizureValue" placeholder="e.g. 250000.00"
                                                    class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- DATE & TIME -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Date &amp; Time of Seizure
                                            </label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="date" id="newSeizureDate" value="{{ $todayDate }}"
                                                    class="w-full px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600">
                                                <input type="time" id="newSeizureTime" value="{{ $currentTime }}"
                                                    class="w-full px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600">
                                            </div>
                                        </div>

                                        <!-- SEIZURE LOCATION -->
                                        <div class="col-span-full md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Seizure Location / Scene
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureLocation" placeholder="e.g. Tema Port Container Terminal 3, Gate 4"
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- METHOD OF CONCEALMENT -->
                                        <div class="col-span-full md:col-span-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Method of Concealment
                                            </label>
                                            <input type="text" id="newSeizureConcealment" placeholder="e.g. False bottom of shipping crate / engine compartment"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>

                                        <!-- SEIZING OFFICERS (MULTI-ROW) -->
                                        <div class="col-span-full md:col-span-2">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                    Seizing Officer(s) <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <button type="button" onclick="addSeizureOfficerRow()"
                                                    class="inline-flex items-center text-[10px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                                    Add Seizing Officer
                                                </button>
                                            </div>
                                            <div id="seizureOfficersContainer" class="space-y-2">
                                                <div class="seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        <input type="text" class="seizure-officer-name w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600"
                                                            value="{{ $officerName }}" placeholder="Officer Full Name">
                                                        <input type="text" class="seizure-officer-service-id w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 font-mono-code"
                                                            placeholder="Service / Staff ID (optional)">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @include('partials.seized_item_source_custody_fields', ['showSourceOfExhibits' => false])

                                        <!-- REASON FOR SEIZURE -->
                                        <div class="col-span-full md:col-span-1">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Reason for Seizure <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <textarea id="newSeizureReason" rows="3"
                                                placeholder="e.g. Intercepted in shipping container without consignee present."
                                                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 resize-none placeholder:text-slate-400"></textarea>
                                        </div>

                                        <!-- REMARKS / OBSERVATIONS -->
                                        <div class="col-span-full md:col-span-1">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Remarks / Observations
                                            </label>
                                            <textarea id="newSeizureRemark" name="exhibit_remark" rows="3"
                                                placeholder="e.g. Custody observations, physical packaging seal numbers, condition when lodged..."
                                                class="seizure-remark-input w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 resize-none placeholder:text-slate-400"></textarea>
                                        </div>

                                        <!-- PHOTO UPLOAD -->
                                        <div class="col-span-full pt-2">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Evidence Photos &amp; Visual Documentation
                                            </label>
                                            <div id="seizurePhotoDropzone"
                                                class="p-5 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl bg-white text-center cursor-pointer transition-all"
                                                onclick="document.getElementById('fileSeizurePhoto').click()">
                                                <div class="flex flex-col items-center justify-center space-y-1">
                                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span class="text-xs font-bold text-slate-700">Click or drag &amp; drop evidence photos</span>
                                                    <span class="text-[10px] text-slate-500">PNG, JPG, WEBP photos up to 15MB</span>
                                                </div>
                                            </div>
                                            <input type="file" id="fileSeizurePhoto" accept="image/*" multiple class="hidden" onchange="previewSeizurePhotos(event)">
                                            <div id="seizurePhotosGrid" class="hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-3"></div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- FORM ACTION BUTTONS -->
                    <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0 shadow-2xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase font-mono-code">Finalize &amp; Save Seized Exhibit(s)</h4>
                                <p class="text-[11px] text-slate-500 font-medium">Save seized exhibit(s) without a suspect to the Exhibit Vault. Suspect biodata can be linked later when an arrest is made.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                            <a href="{{ url('/Evidence_Management/vault') }}"
                                class="px-5 py-3 bg-white border border-slate-300 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-50 transition-colors cursor-pointer text-center">
                                Cancel
                            </a>
                            <button type="button" onclick="processAddExhibit()" id="submitArrestDocketBtn"
                                class="px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center space-x-2 border-none cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <span>Save Exhibit</span>
                            </button>
                        </div>
                    </div>
                </form>

            </main>
        </div>
    </div>

    <!-- SEIZED PROPERTY QR CODE DOWNLOAD POPOVER MODAL -->
    <div id="seizedPropertyQrDownloadModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="bg-white rounded-3xl border border-emerald-200 shadow-2xl max-w-2xl w-full p-6 sm:p-7 space-y-5">
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div class="flex items-start space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-md shadow-slate-200/60">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                                VAULT EXHIBIT LODGED
                            </span>
                            <span id="modalCaseRefBadge" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-extrabold bg-blue-100 text-blue-900 border border-blue-200 uppercase tracking-wider">
                            </span>
                        </div>
                        <h3 class="text-lg font-extrabold text-slate-900 mt-1">
                            Evidence Lodged Successfully!
                        </h3>
                        <p class="text-xs text-slate-600 font-medium">
                            Official Seized Property Tag(s) generated. Would you like to download the evidence QR code badge for physical tagging?
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeSeizedPropertyQrModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer" title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Content: Badge Container -->
            <div id="seizedPropertyModalItemsList" class="space-y-4 max-h-[60vh] overflow-y-auto pr-1"></div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <span class="text-[11px] text-slate-500 font-medium">Notice auto-closes in 1 min</span>
                <div class="flex items-center space-x-2.5 w-full sm:w-auto">
                    <a id="modalReviewCaseBtn" href="#"
                        class="hidden flex-1 sm:flex-none px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors cursor-pointer border border-blue-600 inline-flex items-center justify-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>View in Case Review</span>
                    </a>
                    <button type="button" onclick="closeSeizedPropertyQrModal()"
                        class="flex-1 sm:flex-none px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer border border-slate-200">
                        Continue to Vault
                    </button>
                    <button type="button" id="modalDownloadAllBtn" onclick="downloadAllModalBadges()"
                        class="flex-1 sm:flex-none px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-600/30 transition-all cursor-pointer inline-flex items-center justify-center gap-2 border-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download QR Badge (PNG)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- VALIDATION ERROR MODAL -->
    <div id="validationErrorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl border border-red-200 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-start space-x-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Missing Required Fields</h3>
                    <p class="text-xs text-slate-500">Please provide the following required exhibit details:</p>
                </div>
            </div>
            <ul id="validationErrorList" class="space-y-2 text-xs font-semibold text-red-700"></ul>
            <div class="flex justify-end pt-2">
                <button type="button" onclick="document.getElementById('validationErrorModal').classList.add('hidden')"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Dismiss
                </button>
            </div>
        </div>
    </div>

    <script>
        window.IS_SEIZURES_ONLY_MODE = true;
        let seizedExhibitDataUrls = [];
        let isExhibitSubmitting = false;
        let seizedQrModalTimer = null;
        window.activeModalBadges = [];

        // ── Assign Unique Property IDs to Blocks ──
        function generateRandomPropertyId() {
            const rnd = Math.floor(10000 + Math.random() * 90000);
            return `NACOC/EMU/${rnd}`;
        }

        function assignUniquePropertyIdsToBlocks() {
            const blocks = document.querySelectorAll('.seizure-block');
            const used = new Set();
            blocks.forEach(block => {
                let id = (block.getAttribute('data-property-id') || '').trim();
                if (!id || id === 'NACOC/EMU/00241' || !/^NACOC\/EMU\/\d{5}$/.test(id) || used.has(id)) {
                    do {
                        id = generateRandomPropertyId();
                    } while (used.has(id));
                    block.setAttribute('data-property-id', id);
                }
                used.add(id);
                const badge = block.querySelector('.property-id-badge');
                if (badge) badge.innerText = `ID: ${id}`;
            });
        }

        // ── Block Template Cloner ──
        let pristineBlockTemplate = null;
        document.addEventListener('DOMContentLoaded', function() {
            assignUniquePropertyIdsToBlocks();
            const first = document.querySelector('.seizure-block');
            if (first) {
                pristineBlockTemplate = first.cloneNode(true);
            }

            // Close panels when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#exhibitSelectWrapper')) {
                    document.querySelectorAll('#exhibitListPanel').forEach(p => p.classList.add('hidden'));
                }
                if (!e.target.closest('#drugSelectWrapper')) {
                    document.querySelectorAll('#drugListPanel').forEach(p => p.classList.add('hidden'));
                }
                if (!e.target.closest('#pkgSelectWrapper')) {
                    document.querySelectorAll('#pkgListPanel').forEach(p => p.classList.add('hidden'));
                }
                if (!e.target.closest('#unitSelectWrapper')) {
                    document.querySelectorAll('#unitListPanel').forEach(p => p.classList.add('hidden'));
                }
            });

            // Drag and Drop
            const dropzone = document.getElementById('seizurePhotoDropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(evt => {
                    dropzone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('border-emerald-600', 'bg-emerald-50/50');
                    });
                });
                ['dragleave', 'drop'].forEach(evt => {
                    dropzone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('border-emerald-600', 'bg-emerald-50/50');
                    });
                });
                dropzone.addEventListener('drop', (e) => {
                    const files = Array.from(e.dataTransfer.files || []).filter(f => f.type.startsWith('image/'));
                    if (files.length) handleImageFiles(files);
                });
            }
        });

        function getSeizureBlockFromEvent(e) {
            if (!e) return document.querySelector('.seizure-block');
            const target = e.target || e.srcElement;
            return target?.closest('.seizure-block') || document.querySelector('.seizure-block');
        }

        // ── Dropdown Handlers ──
        function openExhibitListPanel(input) {
            const root = input.closest('.seizure-block') || document;
            const panel = root.querySelector('#exhibitListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function filterExhibitList(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            const panel = root.querySelector('#exhibitListPanel');
            if (panel) panel.classList.remove('hidden');
            root.querySelectorAll('.exhibit-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
            const hidden = root.querySelector('#newSeizureCategory');
            if (hidden) hidden.value = input.value.trim();
        }

        function filterExhibitListInner(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            root.querySelectorAll('.exhibit-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function selectExhibitCategory(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input = root.querySelector('#newSeizureCategory');
            const display = root.querySelector('#exhibitSearchInput');
            const panel = root.querySelector('#exhibitListPanel');
            if (input) input.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');

            // Category specific input display
            const isDrugs = (val === 'Drugs');
            const isMoney = (val === 'Money' || val === 'Cash');
            const isWeapons = (val === 'Weapons');
            const isAmmo = (val === 'Ammunition');
            const isDevices = (val === 'Electronic Devices');

            const drugWrap = root.querySelector('#drugSelectWrapper');
            const wpnWrap = root.querySelector('#weaponSelectWrapper');
            const currWrap = root.querySelector('#currencySelectWrapper');
            const ammoWrap = root.querySelector('#ammoSelectWrapper');
            const devWrap = root.querySelector('#deviceSelectWrapper');
            const textWrap = root.querySelector('#commodityTextWrapper');

            if (drugWrap) drugWrap.classList.toggle('hidden', !isDrugs);
            if (wpnWrap) wpnWrap.classList.toggle('hidden', !isWeapons);
            if (currWrap) currWrap.classList.toggle('hidden', !isMoney);
            if (ammoWrap) ammoWrap.classList.toggle('hidden', !isAmmo);
            if (devWrap) devWrap.classList.toggle('hidden', !isDevices);
            if (textWrap) textWrap.classList.toggle('hidden', (isDrugs || isWeapons || isMoney || isAmmo || isDevices));

            const typeSel = root.querySelector('.exhibit-type-select');
            if (typeSel) typeSel.value = isDrugs ? 'Drug Exhibit' : 'Non-Drug Exhibit';
        }

        function openDrugListPanel(input) {
            const root = input.closest('.seizure-block') || document;
            const panel = root.querySelector('#drugListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function filterDrugList(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            const panel = root.querySelector('#drugListPanel');
            if (panel) panel.classList.remove('hidden');
            root.querySelectorAll('.drug-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
            const hidden = root.querySelector('#newSeizureNameDrug');
            if (hidden) hidden.value = input.value.trim();
        }

        function filterDrugListInner(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            root.querySelectorAll('.drug-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function selectDrugType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input = root.querySelector('#newSeizureNameDrug');
            const display = root.querySelector('#drugSearchInput');
            const panel = root.querySelector('#drugListPanel');
            if (input) input.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
        }

        function openPkgListPanel(input) {
            const root = input.closest('.seizure-block') || document;
            const panel = root.querySelector('#pkgListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function filterPackagingList(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            const panel = root.querySelector('#pkgListPanel');
            if (panel) panel.classList.remove('hidden');
            root.querySelectorAll('.pkg-item').forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
            const hidden = root.querySelector('#newSeizurePackaging');
            if (hidden) hidden.value = input.value.trim();
        }

        function filterPackagingListInner(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            root.querySelectorAll('.pkg-item').forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        function selectPackagingType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input = root.querySelector('#newSeizurePackaging');
            const display = root.querySelector('#pkgSearchInput');
            const panel = root.querySelector('#pkgListPanel');
            if (input) input.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
        }

        function openUnitListPanel(input) {
            const root = input.closest('.seizure-block') || document;
            const panel = root.querySelector('#unitListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function filterUnitList(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            const panel = root.querySelector('#unitListPanel');
            if (panel) panel.classList.remove('hidden');
            root.querySelectorAll('.unit-item').forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
            const hidden = root.querySelector('#newSeizureUnit');
            if (hidden) hidden.value = input.value.trim();
        }

        function filterUnitListInner(input) {
            const root = input.closest('.seizure-block') || document;
            const query = (input.value || '').toLowerCase().trim();
            root.querySelectorAll('.unit-item').forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input = root.querySelector('#newSeizureUnit');
            const display = root.querySelector('#unitSearchInput');
            const panel = root.querySelector('#unitListPanel');
            if (input) input.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
        }

        // ── Officers Multi-Row ──
        function addSeizureOfficerRow(e) {
            const root = getSeizureBlockFromEvent(e);
            const container = root.querySelector('#seizureOfficersContainer');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl';
            row.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <input type="text" class="seizure-officer-name w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600" placeholder="Officer Full Name">
                    <div class="flex items-center gap-1.5">
                        <input type="text" class="seizure-officer-service-id w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="Service / Staff ID">
                        <button type="button" onclick="this.closest('.seizure-officer-row').remove()" class="p-2 text-red-500 hover:bg-red-50 rounded-lg border border-red-200" title="Remove Officer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(row);
        }

        function getSeizureOfficersFormatted(block) {
            const container = block.querySelector('#seizureOfficersContainer');
            if (!container) return null;
            const names = [];
            container.querySelectorAll('.seizure-officer-row').forEach(row => {
                const n = (row.querySelector('.seizure-officer-name')?.value || '').trim();
                const id = (row.querySelector('.seizure-officer-service-id')?.value || '').trim();
                if (n && id) names.push(`${n} (${id})`);
                else if (n) names.push(n);
                else if (id) names.push(`(ID: ${id})`);
            });
            return names.length ? names.join('; ') : '{{ $officerName }}';
        }

        // ── Multiple Exhibit Blocks (Counter / Add / Remove) ──
        function addSeizureBlock() {
            const container = document.getElementById('seizuresListContainer');
            if (!container || !pristineBlockTemplate) return;
            const clone = pristineBlockTemplate.cloneNode(true);
            const count = container.querySelectorAll('.seizure-block').length + 1;

            clone.setAttribute('data-seizure-index', count);
            clone.removeAttribute('data-property-id');
            const title = clone.querySelector('.seizure-title');
            if (title) title.innerText = `Seized Item ${count}`;
            const badge = clone.querySelector('.seizure-number-badge');
            if (badge) badge.innerText = count;

            // Clear values
            clone.querySelectorAll('input:not([type="hidden"]), textarea').forEach(inp => {
                if (inp.id === 'newSeizureDate') inp.value = '{{ $todayDate }}';
                else if (inp.id === 'newSeizureTime') inp.value = '{{ $currentTime }}';
                else if (!inp.classList.contains('seizure-officer-name')) inp.value = '';
            });
            clone.querySelectorAll('input[type="hidden"]').forEach(inp => inp.value = '');
            clone.querySelectorAll('select').forEach(sel => sel.selectedIndex = 0);

            // Show remove button
            const removeBtn = clone.querySelector('.remove-seizure-btn');
            if (removeBtn) removeBtn.classList.remove('hidden');

            container.appendChild(clone);
            assignUniquePropertyIdsToBlocks();

            const counter = document.getElementById('seizureCountInput');
            if (counter) counter.value = count;
        }

        function removeSeizureBlock(btn) {
            const block = btn.closest('.seizure-block');
            const container = document.getElementById('seizuresListContainer');
            if (!block || !container) return;
            if (container.querySelectorAll('.seizure-block').length <= 1) return;

            block.remove();

            // Renumber remaining blocks
            container.querySelectorAll('.seizure-block').forEach((b, idx) => {
                const num = idx + 1;
                b.setAttribute('data-seizure-index', num);
                const title = b.querySelector('.seizure-title');
                if (title) title.innerText = `Seized Item ${num}`;
                const badge = b.querySelector('.seizure-number-badge');
                if (badge) badge.innerText = num;
                const rBtn = b.querySelector('.remove-seizure-btn');
                if (rBtn) rBtn.classList.toggle('hidden', idx === 0 && container.querySelectorAll('.seizure-block').length === 1);
            });

            const counter = document.getElementById('seizureCountInput');
            if (counter) counter.value = container.querySelectorAll('.seizure-block').length;
        }

        function setSeizureBlocksCount(val) {
            const target = Math.max(1, Math.min(50, parseInt(val, 10) || 1));
            const container = document.getElementById('seizuresListContainer');
            let current = container.querySelectorAll('.seizure-block').length;
            while (current < target) {
                addSeizureBlock();
                current++;
            }
            while (current > target) {
                const blocks = container.querySelectorAll('.seizure-block');
                const last = blocks[blocks.length - 1];
                if (last) last.remove();
                current--;
            }
        }

        // ── Evidence Photos Preview ──
        function handleImageFiles(files) {
            let loaded = 0;
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    seizedExhibitDataUrls.push(e.target.result);
                    loaded++;
                    if (loaded === files.length) renderSeizurePhotosGrid();
                };
                reader.readAsDataURL(file);
            });
        }

        function previewSeizurePhotos(e) {
            const files = Array.from(e.target.files || []);
            if (files.length) handleImageFiles(files);
        }

        function renderSeizurePhotosGrid() {
            const grid = document.getElementById('seizurePhotosGrid');
            if (!grid) return;
            grid.innerHTML = '';
            if (seizedExhibitDataUrls.length === 0) {
                grid.classList.add('hidden');
                return;
            }
            grid.classList.remove('hidden');
            seizedExhibitDataUrls.forEach((url, i) => {
                const thumb = document.createElement('div');
                thumb.className = 'relative group aspect-square rounded-xl overflow-hidden border border-slate-200 shadow-2xs';
                thumb.innerHTML = `
                    <img src="${url}" class="w-full h-full object-cover">
                    <button type="button" onclick="removeSeizurePhoto(${i})" class="absolute top-1 right-1 p-1 bg-red-600/90 hover:bg-red-700 text-white rounded-md text-[10px] opacity-90 group-hover:opacity-100 transition-opacity">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                `;
                grid.appendChild(thumb);
            });
        }

        function removeSeizurePhoto(index) {
            seizedExhibitDataUrls.splice(index, 1);
            renderSeizurePhotosGrid();
        }

        // ── Extract Exhibit Block Data ──
        function extractBlockData(block, idx) {
            const cat = (block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || '').trim();
            const drugName = (block.querySelector('#newSeizureNameDrug')?.value || block.querySelector('#drugSearchInput')?.value || '').trim();
            const wpnName = (block.querySelector('#newSeizureNameWeapon')?.value || block.querySelector('#weaponSearchInput')?.value || '').trim();
            const currName = (block.querySelector('#newSeizureNameCurrency')?.value || block.querySelector('#currencySearchInput')?.value || '').trim();
            const ammoName = (block.querySelector('#newSeizureNameAmmo')?.value || block.querySelector('#ammoSearchInput')?.value || '').trim();
            const devName = (block.querySelector('#newSeizureNameDevice')?.value || block.querySelector('#deviceSearchInput')?.value || '').trim();
            const textName = (block.querySelector('#newSeizureName')?.value || '').trim();

            const commodityName = drugName || wpnName || currName || ammoName || devName || textName || '';
            const qty = (block.querySelector('#newSeizureQuantity')?.value || '').trim();
            const pkg = (block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '').trim();
            const weight = (block.querySelector('#newSeizureWeight')?.value || '').trim();
            const unit = (block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '').trim();
            const val = (block.querySelector('#newSeizureValue')?.value || '').trim();

            if (!cat && !commodityName && !qty && !weight) {
                return null;
            }

            const propId = block.getAttribute('data-property-id') || generateRandomPropertyId();
            block.setAttribute('data-property-id', propId);

            return {
                item_number: idx + 1,
                property_id: propId,
                exhibit_category: cat || (drugName ? 'Drugs' : (wpnName ? 'Weapons' : (currName ? 'Money' : 'General Contraband'))),
                exhibit_type: block.querySelector('.exhibit-type-select')?.value || ((cat === 'Drugs' || drugName) ? 'Drug Exhibit' : 'Non-Drug Exhibit'),
                drug_classification: block.querySelector('.drug-classification-select')?.value || 'N/A',
                commodity_name: commodityName || cat || 'Seized Commodity',
                name: commodityName || cat || 'Seized Commodity',
                seizure_name: commodityName,
                seizure_name_drug: drugName,
                seizure_name_weapon: wpnName,
                seizure_name_currency: currName,
                seizure_quantity: qty,
                amount: (cat === 'Money' || cat === 'Currency' || cat === 'Cash') ? (qty || val) : '',
                seizure_packaging: pkg,
                seizure_weight: weight,
                seizure_unit: unit,
                seizure_value: val,
                seizure_concealment: (block.querySelector('#newSeizureConcealment')?.value || '').trim(),
                seizure_date: block.querySelector('#newSeizureDate')?.value || '{{ $todayDate }}',
                seizure_time: block.querySelector('#newSeizureTime')?.value || '{{ $currentTime }}',
                seizure_location: (block.querySelector('#newSeizureLocation')?.value || '').trim(),
                seizure_officer: getSeizureOfficersFormatted(block),
                source_of_exhibits: (block.querySelector('#newSeizureSourceOfExhibits')?.value || block.querySelector('.seizure-source-of-exhibits-input')?.value || '').trim(),
                investigating_officer: (block.querySelector('#newSeizureInvestigatingOfficer')?.value || block.querySelector('.seizure-investigating-officer-input')?.value || '').trim(),
                source_requesting_unit: (block.querySelector('#newSeizureSourceRequestingUnit')?.value || block.querySelector('.seizure-source-requesting-unit-input')?.value || '').trim(),
                seizure_reason: (block.querySelector('#newSeizureReason')?.value || '').trim(),
                exhibit_remark: (block.querySelector('#newSeizureRemark')?.value || '').trim(),
                status: 'Seized'
            };
        }

        // ── Main Save Handler ──
        function processAddExhibit() {
            if (isExhibitSubmitting) return;

            assignUniquePropertyIdsToBlocks();

            const blocks = document.querySelectorAll('.seizure-block');
            const seizures = [];
            blocks.forEach((b, i) => {
                const d = extractBlockData(b, i);
                if (d) seizures.push(d);
            });

            if (seizures.length === 0) {
                showValidationModal(['At least one Exhibit Commodity (Category & Name) must be specified.']);
                return;
            }

            // Set loading state
            isExhibitSubmitting = true;
            const btn = document.getElementById('submitArrestDocketBtn');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Saving to Vault...</span>
                `;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('/Evidence_Management/exhibits/store-unassigned', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    seizures: seizures,
                    evidence_photos: seizedExhibitDataUrls
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    let msg = data.message || 'Validation failed. Please check the exhibit details.';
                    if (data.errors) {
                        msg += '\n• ' + Object.values(data.errors).flat().join('\n• ');
                    }
                    throw new Error(msg);
                }
                return data;
            })
            .then((data) => {
                resetSubmitButton();
                openQrDownloadModal(seizures, data);
            })
            .catch(err => {
                resetSubmitButton();
                const errs = (err.message || 'Error occurred').split('\n• ').filter(Boolean);
                showValidationModal(errs);
            });
        }

        function resetSubmitButton() {
            isExhibitSubmitting = false;
            const btn = document.getElementById('submitArrestDocketBtn');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <span>Save Exhibit</span>
                `;
            }
        }

        function showValidationModal(items) {
            const modal = document.getElementById('validationErrorModal');
            const list = document.getElementById('validationErrorList');
            if (!modal || !list) return;
            list.innerHTML = items.map(t => `<li class="p-2 bg-red-50 border border-red-200 rounded-lg">${t}</li>`).join('');
            modal.classList.remove('hidden');
        }

        // ── QR Code Tagging & Download Modal ──
        function renderNacocQRCode(container, text, size) {
            if (!container) return;
            container.innerHTML = '';
            if (typeof QRCode === 'undefined') return;
            new QRCode(container, {
                text: text,
                width: size || 180,
                height: size || 180,
                colorDark: "#0B6B3A",
                colorLight: "#FFFFFF",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        function buildExhibitQrText(d) {
            return [
                'SEIZED PROPERTY',
                'NACOC - EVIDENCE MANAGEMENT',
                '----------------------------------------',
                `PROPERTY ID: ${d.property_id}`,
                `STATUS: ${d.status || 'Seized'}`,
                `COMMODITY: ${d.commodity_name}`,
                `CATEGORY: ${d.exhibit_category}`,
                d.seizure_weight ? `WEIGHT: ${d.seizure_weight} ${d.seizure_unit || 'kg'}` : '',
                d.seizure_quantity ? `QTY: ${d.seizure_quantity}` : '',
                `LOCATION: ${d.seizure_location || 'Tema Port Command'}`,
                `DATE: ${d.seizure_date || '{{ $todayDate }}'}`,
                `OFFICER: ${d.seizure_officer || '{{ $officerName }}'}`
            ].filter(Boolean).join('\n');
        }

        function createPropertyBadgeCanvas(propId, qrCanvasOrImg) {
            const canvas = document.createElement('canvas');
            const w = 780, h = 330;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, w, h);
            ctx.strokeStyle = '#CBD5E1';
            ctx.lineWidth = 2;
            ctx.strokeRect(1, 1, w - 2, h - 2);

            // Red Header
            ctx.textAlign = 'center';
            ctx.fillStyle = '#EF4444';
            ctx.font = "900 26px 'Bookman Old Style', Bookman, 'URW Bookman', Georgia, serif";
            ctx.fillText('SEIZED PROPERTY', w / 2, 50);

            ctx.fillStyle = '#475569';
            ctx.font = '700 13px system-ui, -apple-system, sans-serif';
            ctx.fillText('NACOC \u2013 EVIDENCE MANAGEMENT', w / 2, 74);

            // Divider
            ctx.strokeStyle = '#E2E8F0';
            ctx.beginPath();
            ctx.moveTo(30, 90);
            ctx.lineTo(w - 30, 90);
            ctx.stroke();

            // Property ID & Status
            ctx.textAlign = 'left';
            ctx.fillStyle = '#64748B';
            ctx.font = '700 12px system-ui, -apple-system, sans-serif';
            ctx.fillText('PROPERTY ID', 40, 140);

            ctx.fillStyle = '#0F172A';
            ctx.font = '900 32px monospace';
            ctx.fillText(propId, 40, 180);

            ctx.fillStyle = '#64748B';
            ctx.font = '700 14px system-ui, -apple-system, sans-serif';
            ctx.fillText('Status: ', 40, 220);
            ctx.fillStyle = '#059669';
            ctx.font = '900 16px system-ui, -apple-system, sans-serif';
            ctx.fillText('Seized', 95, 220);

            // Draw QR
            if (qrCanvasOrImg) {
                try {
                    ctx.drawImage(qrCanvasOrImg, 580, 105, 160, 160);
                } catch (e) {}
            }

            // Bottom Divider & Scan message
            ctx.beginPath();
            ctx.moveTo(30, 280);
            ctx.lineTo(w - 30, 280);
            ctx.stroke();

            ctx.textAlign = 'center';
            ctx.fillStyle = '#64748B';
            ctx.font = '700 11px system-ui, -apple-system, sans-serif';
            ctx.fillText('SCAN TO VIEW PROPERTY DETAILS', w / 2, 305);

            return canvas;
        }

        function openQrDownloadModal(seizures, serverData) {
            const modal = document.getElementById('seizedPropertyQrDownloadModal');
            const list = document.getElementById('seizedPropertyModalItemsList');
            if (!modal || !list) return;

            if (serverData && serverData.case_number) {
                const caseBadge = document.getElementById('modalCaseRefBadge');
                if (caseBadge) {
                    caseBadge.textContent = 'Case Ref: ' + serverData.case_number;
                    caseBadge.classList.remove('hidden');
                }
                const reviewBtn = document.getElementById('modalReviewCaseBtn');
                if (reviewBtn) {
                    reviewBtn.href = serverData.em_review_url || serverData.review_url || ('/Evidence_Management/cases/' + encodeURIComponent(serverData.case_number) + '/review');
                    reviewBtn.classList.remove('hidden');
                }
            }

            list.innerHTML = '';
            window.activeModalBadges = [];

            seizures.forEach((item, idx) => {
                const slotId = `modalQrSlot_${idx}`;
                window.activeModalBadges.push({
                    index: idx,
                    data: item,
                    slotId: slotId
                });

                const card = document.createElement('div');
                card.className = 'space-y-3 p-4 bg-slate-50/70 rounded-2xl border border-slate-200';
                card.innerHTML = `
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-700">Seized Item #${idx + 1}: ${item.commodity_name}</span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="downloadModalQrCode(${idx})"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-slate-100 text-emerald-800 border border-emerald-300 text-xs font-bold rounded-lg shadow-2xs transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                Save QR (PNG)
                            </button>
                            <button type="button" onclick="downloadModalBadgeTag(${idx})"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-lg shadow-xs transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download Tag (PNG)
                            </button>
                        </div>
                    </div>

                    <div class="modal-badge-card w-full max-w-xl mx-auto bg-white border border-slate-300 rounded-xl overflow-hidden text-slate-900 shadow-sm">
                        <div class="pt-4 pb-3 px-4 text-center bg-white">
                            <h3 class="text-base sm:text-lg font-black text-red-500 tracking-[0.18em] uppercase" style="font-family: 'Bookman Old Style', Bookman, 'URW Bookman', 'Georgia', serif;">Seized Property</h3>
                            <p class="text-[11px] font-semibold text-slate-600 tracking-[0.2em] uppercase mt-0.5">NACOC &ndash; Evidence Management</p>
                        </div>
                        <div class="border-b border-slate-300"></div>

                        <div class="px-5 py-5 sm:px-6 flex items-center justify-between gap-5 bg-white">
                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Property ID</div>
                                <div class="text-lg sm:text-2xl font-black text-black tracking-wide font-mono break-all">${item.property_id}</div>
                                <div class="text-xs sm:text-sm text-slate-700 pt-1"><span class="text-slate-500 font-semibold">Status:</span> <span class="font-bold text-black">${item.status || 'Seized'}</span></div>
                            </div>
                            <div class="shrink-0 flex items-center justify-center">
                                <div id="${slotId}" class="w-[120px] h-[120px] sm:w-[130px] sm:h-[130px] flex items-center justify-center overflow-hidden"></div>
                            </div>
                        </div>
                        <div class="border-b border-slate-300"></div>

                        <div class="py-2.5 px-4 text-center bg-white">
                            <p class="text-[10px] sm:text-[11px] font-bold tracking-[0.2em] uppercase text-slate-600">Scan to view property details</p>
                        </div>
                    </div>
                `;
                list.appendChild(card);

                // Render QR into slot
                setTimeout(() => {
                    const slot = document.getElementById(slotId);
                    if (slot) {
                        const qrPayload = buildExhibitQrText(item);
                        renderNacocQRCode(slot, qrPayload, 160);
                    }
                }, 50);
            });

            // Upload badge images to server for vault display
            setTimeout(() => {
                uploadUnassignedExhibitTags(serverData?.case_number || 'UNASSIGNED');
            }, 350);

            modal.classList.remove('hidden');

            if (seizedQrModalTimer) clearTimeout(seizedQrModalTimer);
            seizedQrModalTimer = setTimeout(() => {
                closeSeizedPropertyQrModal();
            }, 60000);
        }

        function uploadUnassignedExhibitTags(caseNumber = 'UNASSIGNED') {
            try {
                const tags = [];
                (window.activeModalBadges || []).forEach(item => {
                    const slot = document.getElementById(item.slotId);
                    const qrCanvas = slot ? slot.querySelector('canvas') : null;
                    const qrImg = slot ? slot.querySelector('img') : null;
                    const qrSource = (qrCanvas && qrCanvas.width > 0) ? qrCanvas : qrImg;
                    const badge = createPropertyBadgeCanvas(item.data.property_id, qrSource);
                    tags.push({
                        property_id: item.data.property_id,
                        image: badge.toDataURL('image/png')
                    });
                });
                if (!tags.length) return;

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('/exhibit-tags', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({ case_number: caseNumber, tags: tags })
                })
                .then(r => r.json())
                .catch(e => console.error('Tag upload warning:', e));
            } catch (e) {
                console.error('Tag upload warning:', e);
            }
        }

        function downloadModalQrCode(idx) {
            const item = (window.activeModalBadges || [])[idx];
            if (!item) return;
            const slot = document.getElementById(item.slotId);
            const qrCanvas = slot ? slot.querySelector('canvas') : null;
            const qrImg = slot ? slot.querySelector('img') : null;
            const dataUrl = (qrCanvas && qrCanvas.width > 0) ? qrCanvas.toDataURL('image/png') : (qrImg ? qrImg.src : null);
            if (!dataUrl) return;

            const link = document.createElement('a');
            link.download = `${item.data.property_id.replace(/[\/\\]/g, '_')}_QRCode.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function downloadModalBadgeTag(idx) {
            const item = (window.activeModalBadges || [])[idx];
            if (!item) return;
            const slot = document.getElementById(item.slotId);
            const qrCanvas = slot ? slot.querySelector('canvas') : null;
            const qrImg = slot ? slot.querySelector('img') : null;
            const qrSource = (qrCanvas && qrCanvas.width > 0) ? qrCanvas : qrImg;

            const badgeCanvas = createPropertyBadgeCanvas(item.data.property_id, qrSource);
            const link = document.createElement('a');
            link.download = `${item.data.property_id.replace(/[\/\\]/g, '_')}_Property_Tag.png`;
            link.href = badgeCanvas.toDataURL('image/png');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function downloadAllModalBadges() {
            (window.activeModalBadges || []).forEach((item, idx) => {
                setTimeout(() => downloadModalBadgeTag(idx), idx * 300);
            });
        }

        function closeSeizedPropertyQrModal() {
            if (seizedQrModalTimer) clearTimeout(seizedQrModalTimer);
            const modal = document.getElementById('seizedPropertyQrDownloadModal');
            if (modal) modal.classList.add('hidden');
            window.location.href = '{{ url("/Evidence_Management/vault") }}';
        }
    </script>
</body>

</html>
