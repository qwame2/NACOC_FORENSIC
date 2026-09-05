<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arrested Person - Suspect Docket | Crime Scene Support</title>

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
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased select-none">

    <div class="flex h-screen overflow-hidden">

        <!-- GENERAL SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden select-text">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" placeholder="Search Arrested Docket #, Suspect Name, Ghana Card, Fingerprint ID..." class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition-all shadow-inner">
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/cases') }}" class="px-4 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all flex items-center space-x-1.5">
                        <span>&larr;</span>
                        <span>Back to Case Directory</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- BANNER -->
                <div class="bg-slate-100 p-6 rounded-2xl border-none shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">SUSPECT DOCKET ENTRY</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">NACOC FORENSIC DIVISION</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Arrested Person (Suspect Docket Portal)</h1>
                        <p class="text-xs text-slate-500 mt-1">Official Field Arrested Logging, Biometric Fingerprint Registrations &amp; Custody Cell Assignments</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1.5 rounded-xl bg-blue-100/80 text-blue-800 font-mono-code font-bold text-xs border-none shadow-xs">
                            14 Persons Detained
                        </span>
                    </div>
                </div>

                <!-- SECTION 1: MASTER CASE TITLE & DOCKET CLASSIFICATION -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
                    <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-600/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Section 1: Master Case Title &amp; Classification</h3>
                                <p class="text-xs text-slate-500">Specify the official legal case title and select single or multiple suspect docket mode</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="caseTypeBadge" class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase tracking-wider">
                                Single Person Mode
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- CASE NAME INPUT -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Case Name / Legal Title <span class="text-red-500">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                </span>
                                <input 
                                    type="text" 
                                    id="caseTitleInput" 
                                    placeholder="e.g. Republic Vs Atto &amp; Ors" 
                                    required 
                                    class="w-full pl-10 pr-3.5 py-3 bg-white border border-red-200 rounded-xl text-slate-900 font-bold outline-none focus:border-red-500 focus:ring-2 focus:ring-red-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal"
                                >
                            </div>
                        </div>

                        <!-- CODE NAME OF OPERATION (OPTIONAL) -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Code Name of Operation</span>
                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Optional</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input 
                                    type="text" 
                                    id="operationCodeNameInput" 
                                    placeholder="e.g. Operation Golden Cobra / Op Storm" 
                                    class="w-full pl-10 pr-3.5 py-3 bg-white border border-red-200 rounded-xl text-slate-900 font-bold outline-none focus:border-red-500 focus:ring-2 focus:ring-red-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal"
                                >
                            </div>
                        </div>

                        <!-- CASE ENTRY TYPE CUSTOM DROPDOWN -->
                        <div class="relative z-30">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Suspect Docket Mode <span class="text-red-500">*</span>
                            </label>
                            <button type="button" id="caseEntryTypeDropdownTrigger" onclick="toggleCaseEntryTypeDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-red-200 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-red-400 outline-none focus:ring-2 focus:ring-red-300 focus:border-red-500 transition-all shadow-2xs group cursor-pointer">
                                <span id="selectedCaseTypeLabel" class="font-extrabold text-slate-900 text-xs">Single Person Case</span>
                                <input type="hidden" id="caseEntryTypeSelect" value="SINGLE">
                                <svg id="caseTypeChevron" class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div id="caseEntryTypeDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 min-w-[280px]">
                                <button type="button" onclick="selectCaseEntryType('SINGLE', 'Single Person Case', event)" class="w-full text-left p-3 text-xs font-bold text-slate-800 hover:bg-blue-50/80 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                    <div>
                                        <div class="font-extrabold text-slate-900 group-hover:text-blue-700 transition-colors">Single Person Case</div>
                                        <div class="text-[10px] text-slate-500 font-normal mt-0.5">Standard docket entry for one primary suspect</div>
                                    </div>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200/80 uppercase">1 Suspect</span>
                                </button>

                                <button type="button" onclick="selectCaseEntryType('MULTIPLE', 'Multiple People Case (Joint Suspects)', event)" class="w-full text-left p-3 text-xs font-bold text-slate-800 hover:bg-amber-50/80 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                    <div>
                                        <div class="font-extrabold text-slate-900 group-hover:text-amber-800 transition-colors">Multiple People Case (Joint Suspects)</div>
                                        <div class="text-[10px] text-slate-500 font-normal mt-0.5">Syndicate Arrested - enables multi-row suspect &amp; mugshot additions</div>
                                    </div>
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200/80 uppercase">Multi-Row</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: MUGSHOT PHOTO UPLOAD -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
                    <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-600/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Section 2: Mugshot (Multi-Angle Photo Capture)</h3>
                                <p class="text-xs text-slate-500">Mandatory suspect facial photo uploads for 3 required angles (Excluding back angle per protocol)</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" id="addMugshotRowBtn" onclick="addMugshotRow()" class="hidden px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Add Mugshot Row</span>
                            </button>
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[10px] font-mono-code font-bold rounded-lg border-none uppercase tracking-wider">
                                3 Angles Required
                            </span>
                        </div>
                    </div>

                    <!-- MUGSHOT ROWS CONTAINER -->
                    <div id="mugshotsContainer" class="space-y-6">
                        <div id="mugshotCard-1" class="space-y-3">
                            <div class="text-xs font-bold text-slate-700 font-mono-code uppercase">Suspect #1 Mugshot Photos</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- ANGLE 1: FRONTAL VIEW -->
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-blue-600 text-white uppercase">Angle 1</span>
                                        <span class="text-[10px] font-bold text-slate-500 font-mono-code">Front View (0&deg;)</span>
                                    </div>
                                    <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('front')">
                                        <img id="preview-front" class="hidden w-full h-full object-cover" alt="Front Mugshot">
                                        <div id="placeholder-front" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span class="text-[10px] font-bold">Upload Front Photo</span>
                                            <span class="text-[9px] text-slate-400">Full Face Direct</span>
                                        </div>
                                    </div>
                                    <input type="file" id="file-front" accept="image/*" class="hidden" onchange="previewMugshot(event, 'front')">
                                    <button type="button" onclick="triggerMugshotUpload('front')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                                        Choose Front Photo
                                    </button>
                                </div>

                                <!-- ANGLE 2: LEFT PROFILE -->
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 2</span>
                                        <span class="text-[10px] font-bold text-slate-500 font-mono-code">Left Profile (90&deg;)</span>
                                    </div>
                                    <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('left-profile')">
                                        <img id="preview-left-profile" class="hidden w-full h-full object-cover" alt="Left Profile Mugshot">
                                        <div id="placeholder-left-profile" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            <span class="text-[10px] font-bold">Upload Left Profile</span>
                                            <span class="text-[9px] text-slate-400">90&deg; Full Left Side</span>
                                        </div>
                                    </div>
                                    <input type="file" id="file-left-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'left-profile')">
                                    <button type="button" onclick="triggerMugshotUpload('left-profile')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                                        Choose Left Profile
                                    </button>
                                </div>

                                <!-- ANGLE 3: RIGHT PROFILE -->
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 3</span>
                                        <span class="text-[10px] font-bold text-slate-500 font-mono-code">Right Profile (90&deg;)</span>
                                    </div>
                                    <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('right-profile')">
                                        <img id="preview-right-profile" class="hidden w-full h-full object-cover" alt="Right Profile Mugshot">
                                        <div id="placeholder-right-profile" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            <span class="text-[10px] font-bold">Upload Right Profile</span>
                                            <span class="text-[9px] text-slate-400">90&deg; Full Right Side</span>
                                        </div>
                                    </div>
                                    <input type="file" id="file-right-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'right-profile')">
                                    <button type="button" onclick="triggerMugshotUpload('right-profile')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                                        Choose Right Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DOCKET LOGGING FORM -->
                <form onsubmit="event.preventDefault(); processNewArrested();" class="space-y-6 text-xs font-medium">

                    <!-- SECTION 3: SUSPECT PERSONAL DETAILS & BOOKING DOCKET -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-600/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 3: Suspect Booking &amp; Personal Details</h3>
                                    <p class="text-xs text-slate-500">Enter suspect legal identity, origin, residential address, marital records, and custody cell allocation</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" id="addSuspectDetailsRowBtn" onclick="addSuspectDetailsRow()" class="hidden px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add Suspect Details Row</span>
                                </button>
                                <span class="text-xs font-mono-code font-bold text-slate-400">NACOC DOCKET FORM #AR-2026</span>
                            </div>
                        </div>

                        <!-- SUSPECTS CONTAINER -->
                        <div id="suspectsContainer" class="space-y-6">
                            <div id="suspectCard-1" class="space-y-6">

                        <!-- SUB-HEADER 3A: LEGAL IDENTITY & ORIGIN -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                                <span>3A. Legal Identity &amp; Origin Details</span>
                            </div>

                            <!-- ROW 1: REF (READONLY), FULL NAME, ALIAS -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                
                                <!-- RAID INCIDENT REFERENCE (READONLY) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Raid Incident Reference</span>
                                        <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200 px-2 py-0.5 rounded font-bold">READONLY</span>
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3.5 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedRaidRef" value="RAID-2026-0419" readonly class="w-full pl-10 pr-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-800 cursor-not-allowed select-none shadow-2xs">
                                    </div>
                                </div>

                                <!-- SUSPECT FULL LEGAL NAME -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Suspect Full Legal Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedName" placeholder="e.g. Kwame Mensah" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- ALIAS / STREET NAME -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Alias / Street Name
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedAlias" placeholder="e.g. Cobra / Jaguar" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 2: GHANA CARD, GENDER, DOB, AGE, NATIONALITY -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                                
                                <!-- GHANA CARD # -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Ghana Card # <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedID" placeholder="GHA-8840291-3" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- CUSTOM GENDER DROPDOWN -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Gender <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" id="genderDropdownTrigger" onclick="toggleGenderDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-600/20 focus:border-red-600 transition-all shadow-2xs group">
                                        <div class="flex items-center space-x-2">
                                            <span id="selectedGenderIcon" class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">♂</span>
                                            <span id="selectedGenderLabel" class="font-bold text-slate-900 text-xs">Male</span>
                                        </div>
                                        <input type="hidden" id="newArrestedGender" value="Male">
                                        <svg id="genderChevron" class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="genderDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                        <button type="button" onclick="selectGender('Male', '♂', 'bg-blue-100 text-blue-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">♂</span>
                                            <span>Male</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Female', '♀', 'bg-rose-100 text-rose-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">♀</span>
                                            <span>Female</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Other / Non-Binary', '⚧', 'bg-purple-100 text-purple-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">⚧</span>
                                            <span>Other / Non-Binary</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Unspecified', '?', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                            <span>Unspecified</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- DATE OF BIRTH (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Date of Birth <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </span>
                                        <input type="date" id="newArrestedDOB" value="" onchange="calculateAgeFromDOB()" oninput="calculateAgeFromDOB()" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- AGE -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Age (Years) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 font-mono-code font-bold text-xs transition-colors">#</span>
                                        <input type="number" id="newArrestedAge" value="" placeholder="Auto-calculated age..." min="0" max="120" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                                    </div>
                                </div>
                                
                                <!-- CUSTOM NATIONALITY DROPDOWN -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Nationality <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" id="natDropdownTrigger" onclick="toggleNationalityDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-600/20 focus:border-red-600 transition-all shadow-2xs group">
                                        <div class="flex items-center space-x-2">
                                            <span id="selectedNationalityBadge" class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold">GH</span>
                                            <span id="selectedNationalityLabel" class="font-bold text-slate-900 text-xs truncate">Ghanaian</span>
                                        </div>
                                        <input type="hidden" id="newArrestedNationality" value="Ghanaian">
                                        <svg id="nationalityChevron" class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="nationalityDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-2 max-h-64 overflow-y-auto">
                                        <div class="relative sticky top-0 bg-white pt-1 pb-2 border-b border-slate-100" onclick="event.stopPropagation()">
                                            <input type="text" id="nationalitySearchInput" onkeyup="filterNationalities()" placeholder="Search nationality..." class="w-full pl-8 pr-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-600/15">
                                            <svg class="w-4 h-4 absolute left-2.5 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <div class="space-y-1" id="nationalityList">
                                            <button type="button" onclick="selectNationality('Ghanaian', 'GH', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Ghanaian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">GH</span></button>
                                            <button type="button" onclick="selectNationality('Nigerian', 'NG', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Nigerian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">NG</span></button>
                                            <button type="button" onclick="selectNationality('Togolese', 'TG', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Togolese</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">TG</span></button>
                                            <button type="button" onclick="selectNationality('Ivorian (Ivory Coast)', 'CI', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Ivorian (Ivory Coast)</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">CI</span></button>
                                            <button type="button" onclick="selectNationality('Burkinabe (Burkina Faso)', 'BF', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Burkinabe (Burkina Faso)</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">BF</span></button>
                                            <button type="button" onclick="selectNationality('Malian', 'ML', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Malian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">ML</span></button>
                                            <button type="button" onclick="selectNationality('Liberian', 'LR', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Liberian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">LR</span></button>
                                            <button type="button" onclick="selectNationality('Sierra Leonean', 'SL', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Sierra Leonean</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">SL</span></button>
                                            <button type="button" onclick="selectNationality('British (United Kingdom)', 'GB', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>British (United Kingdom)</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">GB</span></button>
                                            <button type="button" onclick="selectNationality('American (USA)', 'US', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>American (USA)</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">US</span></button>
                                            <button type="button" onclick="selectNationality('Canadian', 'CA', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Canadian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">CA</span></button>
                                            <button type="button" onclick="selectNationality('Chinese', 'CN', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Chinese</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">CN</span></button>
                                            <button type="button" onclick="selectNationality('Indian', 'IN', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Indian</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">IN</span></button>
                                            <button type="button" onclick="selectNationality('South African', 'ZA', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>South African</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">ZA</span></button>
                                            <button type="button" onclick="selectNationality('German', 'DE', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>German</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">DE</span></button>
                                            <button type="button" onclick="selectNationality('French', 'FR', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>French</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">FR</span></button>
                                            <button type="button" onclick="selectNationality('Lebanese', 'LB', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Lebanese</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">LB</span></button>
                                            <button type="button" onclick="selectNationality('Other / Dual Nationality', 'INT', event)" class="nat-option w-full text-left px-3 py-1.5 text-xs text-slate-800 font-bold hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Other / Dual Nationality</span> <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono-code font-bold">INT</span></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 3: PLACE OF BIRTH, CONTACT PHONE NUMBER, RESIDENTIAL ADDRESS -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                
                                <!-- PLACE OF BIRTH / HOMETOWN (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Place of Birth / Hometown <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedPOB" placeholder="e.g. Kumasi, Ashanti Region" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- CONTACT PHONE NUMBER (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Contact Phone Number(s) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedPhone" placeholder="e.g. +233 24 555 0192" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- RESIDENTIAL ADDRESS (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Residential Address / GPS <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedAddress" placeholder="e.g. H/No 14 East Legon, Accra (GA-142-8809)" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- SUB-HEADER 3B: FAMILY & MARITAL RECORDS -->
                        <div class="space-y-4 pt-2">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span>3B. Marital Record, Spouse / Partner Details &amp; Dependents</span>
                            </div>

                            <!-- ROW 4: MARITAL STATUS & NUMBER OF CHILDREN -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                
                                <!-- CUSTOM MARITAL STATUS DROPDOWN -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Marital / Relationship Status <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" id="maritalDropdownTrigger" onclick="toggleMaritalDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl flex items-center justify-between hover:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-600/20 focus:border-red-600 transition-all shadow-2xs group">
                                        <span id="selectedMaritalLabel" class="font-medium text-slate-400 text-xs">Select Marital Status...</span>
                                        <input type="hidden" id="newArrestedMarital" value="">
                                        <svg id="maritalChevron" class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="maritalDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                        <button type="button" onclick="selectMaritalStatus('Single', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Single</button>
                                        <button type="button" onclick="selectMaritalStatus('Married', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Married (Requires Spouse Info)</button>
                                        <button type="button" onclick="selectMaritalStatus('Dating / In Relationship', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Dating / In Relationship (Requires Partner Info)</button>
                                        <button type="button" onclick="selectMaritalStatus('Divorced', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Divorced</button>
                                        <button type="button" onclick="selectMaritalStatus('Widowed', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Widowed</button>
                                    </div>
                                </div>

                                <!-- CHILDREN / DEPENDENTS STATUS (CHECKBOX + CONDITIONAL INPUT) -->
                                <div class="space-y-2.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                        Children / Dependents Status <span class="text-red-500">*</span>
                                    </label>
                                    
                                    <!-- CHECKBOX CARD -->
                                    <label class="flex items-center space-x-3 p-3 bg-white border border-slate-300 rounded-xl cursor-pointer hover:border-red-500 focus-within:ring-2 focus-within:ring-red-600/20 focus-within:border-red-600 transition-all shadow-2xs select-none">
                                        <input type="checkbox" id="hasChildrenCheckbox" onchange="toggleChildrenInput()" class="w-5 h-5 text-red-600 rounded-md border-slate-300 focus:ring-red-600 focus:ring-2 cursor-pointer accent-red-600">
                                        <span class="text-xs font-bold text-slate-900">Suspect Has Children / Dependents</span>
                                    </label>

                                    <!-- CONDITIONAL NUMBER INPUT CONTAINER -->
                                    <div id="childrenInputContainer" class="hidden relative flex items-center group pt-1">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        </span>
                                        <input type="number" id="newArrestedChildren" min="1" max="20" placeholder="Enter number of children..." class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                            </div>

                            <!-- DYNAMIC CONDITIONAL SPOUSE / DATING PARTNER CARD (NEW) -->
                            <div id="relationshipDetailsBox" class="hidden p-5 bg-red-50/50 rounded-2xl border border-red-200 space-y-4">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    </div>
                                    <div>
                                        <h4 id="relationshipTitle" class="font-extrabold text-red-900 text-xs uppercase tracking-wider">Spouse Information (Marriage Record)</h4>
                                        <p id="relationshipSubtitle" class="text-[11px] text-red-700">Mandatory record for suspect legal spouse identity, phone contact, and occupation</p>
                                    </div>
                                </div>

                                <!-- SPOUSE FIELDS CARD -->
                                <div id="spouseFieldsCard" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Full Legal Name</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpouseName" placeholder="e.g. Abena Mensah" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Contact Phone</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpousePhone" placeholder="e.g. +233 20 884 0129" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Occupation / Address</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A2.36 2.36 0 0019.358 11h-1.55A1.5 1.5 0 0116.3 9.5V7a2 2 0 00-2-2H9.7a2 2 0 00-2 2v2.5A1.5 1.5 0 016.2 11H4.642A2.36 2.36 0 003 13.255V18a2 2 0 002 2h14a2 2 0 002-2v-4.745z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpouseInfo" placeholder="e.g. Trader, Tema Sector 1" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                </div>

                                <!-- DATING PARTNER FIELDS CARD (FOR DATING STATUS) -->
                                <div id="partnerFieldsCard" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Full Legal Name</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerName" placeholder="e.g. Ama Serwaa" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Contact Phone</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerPhone" placeholder="e.g. +233 24 100 2938" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Residential Info</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerInfo" placeholder="e.g. Resident, East Legon" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-HEADER 3C: Offence CHARGES & CUSTODY ALLOCATION -->
                        <div class="space-y-4 pt-2">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                                <span>3C. Offence Charges &amp; Custody Holding Facility</span>
                            </div>

                            <!-- ROW 5: RAID SCENE LOCATION, OFFENSE, DATE/TIME OF Arrested & HOLDING FACILITY -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Place of Raid Scene &amp; Offence <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedRaidLocation" placeholder="e.g. East Legon Sector 4, Accra" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Offence / Charge Description <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex group">
                                        <span class="absolute left-3.5 top-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                        </span>
                                        <textarea id="newArrestedOffense" rows="3" placeholder="e.g. High-Purity Narcotics Trafficking &amp; Illegal Firearm Possession, Breach of Peace..." required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400 resize-y min-h-[96px]"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 6: DATE OF Arrested, TIME OF Arrested & HOLDING FACILITY -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
                                <!-- DATE OF Arrested -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Date of Arrested <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </span>
                                        <input type="date" id="newArrestedDate" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- TIME OF Arrested -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Time of Arrested <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </span>
                                        <input type="time" id="newArrestedTime" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- CUSTODY HOLDING FACILITY -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Assigned Custody Holding Facility <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedFacility" placeholder="e.g. NACOC High-Security Holding Cell Sector B" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: SEIZURES (COMMODITIES) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
                    <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center font-bold shadow-md shadow-red-600/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Section 4: Seizures (Commodities)</h3>
                                <p class="text-xs text-slate-500">Record illicit substance types, gross weight/quantity, street valuation, and evidence vault allocation</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 bg-red-50 text-red-700 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase tracking-wider">
                                Contraband Inventory
                            </span>
                        </div>
                    </div>

                            <!-- SEIZURE STATUS CHECKBOX CARD -->
                            <div class="space-y-2.5">
                                <label class="flex items-center space-x-3 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:border-red-500 focus-within:ring-2 focus-within:ring-red-600/20 focus-within:border-red-600 transition-all shadow-2xs select-none">
                                    <input type="checkbox" id="hasSeizuresCheckbox" onchange="toggleSeizuresContainer()" class="w-5 h-5 text-red-600 rounded-md border-slate-300 focus:ring-red-600 focus:ring-2 cursor-pointer accent-red-600">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-extrabold text-slate-900">Seized Contraband / Illicit Commodities Recorded During Arrested</span>
                                        <span class="text-[11px] text-slate-500 font-medium">Check this box if any narcotics, weapons, cash, or contraband commodities were confiscated from this suspect</span>
                                    </div>
                                </label>
                            </div>

                            <!-- CONDITIONAL SEIZURES DETAILS CONTAINER -->
                            <div id="seizuresDetailsContainer" class="hidden p-5 bg-red-50/40 rounded-2xl border border-red-200/90 space-y-5">
                                
                                <div class="flex items-center justify-between border-b border-red-200/60 pb-3">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                        <div>
                                            <h4 class="font-extrabold text-red-900 text-xs uppercase tracking-wider">Seized Commodities &amp; Contraband Manifest</h4>
                                            <p class="text-[11px] text-red-700">Record illicit substance types, gross weight/quantity, street valuation, and evidence vault allocation</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 bg-red-100 text-red-800 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">Evidence Tagging Active</span>
                                </div>

                                <!-- SEIZURES INPUT GRID -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    
                                    <!-- EXHIBIT (COMMODITY CATEGORY) - SELECT2 DROPDOWN -->
                                    <div class="relative z-30">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                            Exhibit (Commodity Category) <span class="text-red-500">*</span>
                                        </label>
                                        <div id="exhibitSelectWrapper" class="relative">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="exhibitSearchInput"
                                                    placeholder="Select exhibit category..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-red-500 focus:ring-2 focus:ring-red-300 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-pointer"
                                                    readonly
                                                    onclick="this.removeAttribute('readonly'); document.getElementById('exhibitListPanel').classList.remove('hidden');"
                                                    oninput="filterExhibitList()"
                                                    onfocus="document.getElementById('exhibitListPanel').classList.remove('hidden')"
                                                >
                                                <input type="hidden" id="newSeizureCategory" value="">
                                            </div>
                                            <div id="exhibitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="exhibitSearchInner" placeholder="Type to filter categories..." oninput="filterExhibitListInner()" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-red-300 transition-all">
                                                </div>
                                                <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Drugs', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Drugs</div><div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div></div>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">DRG</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Money', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Money</div><div class="text-[10px] text-slate-500 font-normal">Cash, Foreign Currencies, Laundering</div></div>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">CASH</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Gold', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Gold</div><div class="text-[10px] text-slate-500 font-normal">Bullion, Bars, Smuggled Metals</div></div>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold">MET</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Vehicles', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Vehicles</div><div class="text-[10px] text-slate-500 font-normal">Automobiles, Vessels, Trucks</div></div>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 px-2 py-0.5 rounded font-bold">VEH</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Other Exhibits', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Other Exhibits</div><div class="text-[10px] text-slate-500 font-normal">Weapons, Hardware, Devices</div></div>
                                                            <span class="text-[10px] font-mono-code bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">MISC</span>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                    <div id="commodityFieldWrapper">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Primary Commodity Name <span class="text-red-500">*</span></label>

                                        <!-- TEXT INPUT (shown for non-drug categories) -->
                                        <div id="commodityTextWrapper" class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureName" placeholder="e.g. Cocaine (High Purity Slabs)" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>

                                        <!-- DRUG SELECT2 DROPDOWN (shown when Drugs is selected) -->
                                        <div id="drugSelectWrapper" class="hidden relative z-40">
                                            <!-- Search input -->
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-red-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="drugSearchInput"
                                                    placeholder="Search drug type..."
                                                    autocomplete="off"
                                                    oninput="filterDrugList()"
                                                    onfocus="document.getElementById('drugListPanel').classList.remove('hidden')"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-red-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-red-500 focus:ring-2 focus:ring-red-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                    readonly
                                                    onclick="this.removeAttribute('readonly'); document.getElementById('drugListPanel').classList.remove('hidden');"
                                                >
                                                <input type="hidden" id="newSeizureNameDrug" name="newSeizureName">
                                            </div>
                                            <!-- Dropdown panel -->
                                            <div id="drugListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-red-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="drugSearchInner" placeholder="Type to filter drugs..." oninput="filterDrugListInner()" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-red-300 transition-all">
                                                </div>
                                                <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Opioids & Analgesics -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Opioids &amp; Analgesics</li>
                                                    <li class="drug-item" onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>Heroin</span><span class="text-[10px] font-mono-code bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold">Brown Sugar</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Fentanyl (Synthetic Opioid)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Morphine (Raw Opium Extract)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Morphine (Raw Opium Extract)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>Tramadol</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">High-Dose</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Codeine (Purple Drank)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Oxycodone (OxyContin Tablets)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Oxycodone (OxyContin)</button></li>

                                                    <!-- Stimulants -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Stimulants &amp; Cocaine-Class</li>
                                                    <li class="drug-item" onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>Cocaine (Powder)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold">COC</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Crack Cocaine (Rock Form)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>Methamphetamine (Ice)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">METH</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Amphetamine (Speed / Pep Pills)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Amphetamine (Speed Pills)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>MDMA (Ecstasy / Molly)</span><span class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">XTC</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Khat (Catha Edulis Fresh Leaves / Bundles)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Khat (Catha Edulis)</button></li>

                                                    <!-- Cannabis -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Cannabis &amp; Derivatives</li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors flex items-center justify-between"><span>Cannabis / Marijuana</span><span class="text-[10px] font-mono-code bg-green-50 text-green-700 px-1.5 py-0.5 rounded font-bold">THC</span></button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Cannabis Resin (Hashish)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis Oil / THC Concentrate (Wax / Dabs)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Cannabis Oil / THC Concentrate</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Synthetic Cannabinoids (Spice/K2)</button></li>

                                                    <!-- Sedatives & Depressants -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Sedatives &amp; Depressants</li>
                                                    <li class="drug-item" onclick="selectDrugType('Rohypnol (Flunitrazepam / Date Rape Drug)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Rohypnol (Flunitrazepam)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('GHB (Gamma-Hydroxybutyrate / Liquid Ecstasy)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">GHB (Liquid Ecstasy)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Diazepam / Valium (Benzodiazepine Tablets)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Diazepam / Valium (Benzo)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Ketamine (Special K / Dissociative Anesthetic)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Ketamine (Special K)</button></li>

                                                    <!-- Hallucinogens -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Hallucinogens &amp; Psychedelics</li>
                                                    <li class="drug-item" onclick="selectDrugType('LSD (Lysergic Acid Diethylamide / Acid Tabs)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">LSD (Acid Tabs)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Psilocybin (Magic Mushrooms / Shrooms)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Psilocybin (Magic Mushrooms)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('PCP (Phencyclidine / Angel Dust)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">PCP (Angel Dust)</button></li>

                                                    <!-- Precursors -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-red-400 uppercase tracking-widest">Precursor Chemicals</li>
                                                    <li class="drug-item" onclick="selectDrugType('Ephedrine / Pseudoephedrine (Meth Precursor)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Ephedrine / Pseudoephedrine</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Acetic Anhydride (Heroin Precursor Chemical)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Acetic Anhydride (Heroin Precursor)</button></li>
                                                    <li class="drug-item" onclick="selectDrugType('Phenylacetic Acid (Amphetamine Precursor)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl transition-colors">Phenylacetic Acid</button></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- CURRENCY SELECT2 DROPDOWN (shown when Money is selected) -->
                                        <div id="currencySelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="currencySearchInput"
                                                    placeholder="Select currency..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-emerald-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                    readonly
                                                    onclick="this.removeAttribute('readonly'); document.getElementById('currencyListPanel').classList.remove('hidden');"
                                                    oninput="filterCurrencyList()"
                                                    onfocus="document.getElementById('currencyListPanel').classList.remove('hidden')"
                                                >
                                                <input type="hidden" id="newSeizureNameCurrency" name="newSeizureName">
                                            </div>
                                            <div id="currencyListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-emerald-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="currencySearchInner" placeholder="Type to filter currencies..." oninput="filterCurrencyListInner()" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-300 transition-all">
                                                </div>
                                                <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- West Africa -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">West Africa</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ghana Cedi (GHS ₵)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ghana Cedi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS ₵</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Nigerian Naira (NGN ₦)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Nigerian Naira</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN ₦</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('CFA Franc BCEAO (XOF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>CFA Franc (BCEAO)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">XOF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Sierra Leonean Leone (SLL)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Sierra Leonean Leone</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SLL</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Liberian Dollar (LRD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Liberian Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">LRD</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Gambian Dalasi (GMD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Gambian Dalasi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GMD</span></button></li>

                                                    <!-- Major Global -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Major Global Currencies</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('US Dollar (USD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>US Dollar</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">USD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Euro (EUR €)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Euro</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">EUR €</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('British Pound Sterling (GBP £)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>British Pound Sterling</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">GBP £</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Swiss Franc (CHF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Swiss Franc</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">CHF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Japanese Yen (JPY ¥)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Japanese Yen</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">JPY ¥</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Chinese Yuan Renminbi (CNY ¥)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Chinese Yuan (Renminbi)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">CNY ¥</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Canadian Dollar (CAD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Canadian Dollar</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">CAD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Australian Dollar (AUD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Australian Dollar</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">AUD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('UAE Dirham (AED)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>UAE Dirham</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">AED</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Saudi Riyal (SAR)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Saudi Riyal</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SAR</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Indian Rupee (INR ₹)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Indian Rupee</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">INR ₹</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('South Korean Won (KRW ₩)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South Korean Won</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">KRW ₩</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Singapore Dollar (SGD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Singapore Dollar</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SGD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Hong Kong Dollar (HKD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Hong Kong Dollar</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">HKD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Russian Ruble (RUB ₽)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Russian Ruble</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">RUB ₽</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Turkish Lira (TRY ₺)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Turkish Lira</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">TRY ₺</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Brazilian Real (BRL R$)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Brazilian Real</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">BRL R$</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Mexican Peso (MXN $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Mexican Peso</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">MXN $</span></button></li>

                                                    <!-- Africa -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Africa</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('South African Rand (ZAR R)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South African Rand</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZAR R</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Kenyan Shilling (KES)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Kenyan Shilling</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">KES</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ethiopian Birr (ETB)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethiopian Birr</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ETB</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Egyptian Pound (EGP £)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Egyptian Pound</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EGP £</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Tanzanian Shilling (TZS)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tanzanian Shilling</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">TZS</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Moroccan Dirham (MAD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Moroccan Dirham</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">MAD</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Angolan Kwanza (AOA)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Angolan Kwanza</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AOA</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Congolese Franc (CDF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Congolese Franc</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CDF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Zambian Kwacha (ZMW)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Zambian Kwacha</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZMW</span></button></li>

                                                    <!-- Crypto -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Cryptocurrencies</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Bitcoin (BTC ₿)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Bitcoin</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">BTC ₿</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ethereum (ETH Ξ)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethereum</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">ETH Ξ</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Tether (USDT)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tether (Stablecoin)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">USDT</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Monero (XMR – Privacy Coin)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Monero (Privacy Coin)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">XMR</span></button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QUANTITY & UNIT -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label id="quantityLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Quantity / Weight</label>
                                            <input type="number" step="0.01" id="newSeizureQuantity" placeholder="e.g. 24.50" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                        <div class="relative z-20">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Unit</label>
                                            <div id="unitSelectWrapper" class="relative">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="unitSearchInput"
                                                        placeholder="Select unit..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-red-500 focus:ring-2 focus:ring-red-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                        readonly
                                                        onclick="this.removeAttribute('readonly'); document.getElementById('unitListPanel').classList.remove('hidden');"
                                                        oninput="filterUnitList()"
                                                        onfocus="document.getElementById('unitListPanel').classList.remove('hidden')"
                                                    >
                                                    <input type="hidden" id="newSeizureUnit" value="">
                                                </div>
                                                <div id="unitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="unitSearchInner" placeholder="Type to filter units..." oninput="filterUnitListInner()" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-red-300 transition-all">
                                                    </div>
                                                    <ul id="unitList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Weight &amp; Mass</li>
                                                        <li class="unit-item" onclick="selectUnit('Kilograms (kg)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Kilograms</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Grams (g)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Grams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Tonnes (t)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Tonnes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">t</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Milligrams (mg)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Milligrams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mg</span></button></li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Volume &amp; Liquid</li>
                                                        <li class="unit-item" onclick="selectUnit('Litres (L)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Litres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Millilitres (mL)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Millilitres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mL</span></button></li>
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Packaging &amp; Quantity</li>
                                                        <li class="unit-item" onclick="selectUnit('Slabs / Blocks')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Slabs / Blocks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Bundles / Sacks')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Bundles / Sacks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BDL</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Pieces / Units')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Pieces / Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCS</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Cartons / Boxes')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Cartons / Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CTN</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Wraps / Rolls')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Wraps / Rolls</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">WRP</span></button></li>
                                                        <li class="unit-item" onclick="selectUnit('Capsules / Tablets')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-xl flex items-center justify-between transition-colors"><span>Capsules / Tablets</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CAP</span></button></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTIMATED STREET VALUE -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Estimated Street Value</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors font-mono-code font-bold text-xs">₵</span>
                                            <input type="text" id="newSeizureValue" placeholder="e.g. GHS 1,500,000 / $120,000" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- EVIDENCE VAULT TAG -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Evidence Vault Tag / Storage Ref</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureVault" placeholder="e.g. NACOC Vault-B Locker 14" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- CONCEALMENT METHOD -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Concealment &amp; Packaging Notes</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureConcealment" placeholder="e.g. Vacuum foil wrapped in false trunk bottom" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- DATE & TIME SEIZED -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- DATE SEIZED -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Date Seized <span class="text-red-500">*</span></label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </span>
                                                <input type="date" id="newSeizureDate" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- TIME SEIZED -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Time Seized</label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </span>
                                                <input type="time" id="newSeizureTime" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- LOCATION SEIZED -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Location Seized <span class="text-red-500">*</span></label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureLocation" placeholder="e.g. Tema Port, Gate 4 — Container Terminal" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- SEIZING OFFICER -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Seizing Officer <span class="text-red-500">*</span></label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureOfficer" placeholder="e.g. DSP Kwame Mensah (Badge No. NACOC-0042)" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- REASON FOR SEIZURE -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reason for Seizure <span class="text-red-500">*</span></label>
                                        <div class="relative flex items-start group">
                                            <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </span>
                                            <textarea id="newSeizureReason" rows="3" placeholder="e.g. Suspected drug trafficking — commodity found concealed in vehicle false compartment during routine checkpoint stop." class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                        </div>
                                    </div>

                                </div>

                                <!-- SEIZED EXHIBIT PHOTO UPLOAD AREA (MULTIPLE IMAGES) -->
                                <div class="pt-2 border-t border-red-200/60 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                            Upload Seized Exhibit Photos / Evidence Images <span class="text-slate-400 font-normal">(Multiple Supported)</span>
                                        </label>
                                        <span id="seizurePhotosCount" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-red-100 text-red-700 border border-red-200">0 Photos Uploaded</span>
                                    </div>

                                    <!-- DROPZONE BOX -->
                                    <div id="seizurePhotoDropzone" onclick="triggerSeizurePhotoUpload()" class="w-full min-h-[90px] bg-white rounded-xl border-2 border-dashed border-red-300 hover:border-red-500 flex flex-col items-center justify-center p-4 cursor-pointer group transition-colors shadow-2xs text-center">
                                        <div id="placeholderSeizureBox" class="flex flex-col items-center space-y-1 text-slate-400 group-hover:text-red-600 transition-colors">
                                            <div class="w-9 h-9 rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-xs font-extrabold text-slate-800 group-hover:text-red-700">Click or drag &amp; drop to upload exhibit photos</span>
                                            <span class="text-[10px] text-slate-500">Select one or multiple PNG, JPG, WEBP evidence photos (up to 15MB each)</span>
                                        </div>
                                    </div>
                                    <input type="file" id="fileSeizurePhoto" accept="image/*" multiple class="hidden" onchange="previewSeizurePhotos(event)">

                                    <!-- DYNAMIC MULTIPLE PHOTOS GALLERY GRID -->
                                    <div id="seizurePhotosGrid" class="hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-1">
                                        <!-- Photo thumbnails dynamically injected here -->
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end space-x-3 border-t border-slate-100">
                                <button type="reset" class="px-5 py-3 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Reset Form</button>
                                <button type="submit" class="px-7 py-3 bg-red-600 text-white font-extrabold rounded-xl hover:bg-red-700 shadow-md shadow-red-600/30 transition-all flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Lock Suspect Arrested Docket</span>
                                </button>
                            </div>
                        </div>
                    </form>


            </main>
        </div>
    </div>

    <script>
        let mugshotCount = 1;
        let suspectCount = 1;

        function handleCaseEntryTypeChange() {
            const select = document.getElementById('caseEntryTypeSelect');
            const badge = document.getElementById('caseTypeBadge');
            const addMugshotBtn = document.getElementById('addMugshotRowBtn');
            const addSuspectBtn = document.getElementById('addSuspectDetailsRowBtn');
            
            if (!select) return;
            
            if (select.value === 'MULTIPLE') {
                if (badge) {
                    badge.innerText = 'Multiple People Case (Joint Suspects)';
                    badge.className = 'px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase tracking-wider';
                }
                if (addMugshotBtn) addMugshotBtn.classList.remove('hidden');
                if (addSuspectBtn) addSuspectBtn.classList.remove('hidden');
            } else {
                if (badge) {
                    badge.innerText = 'Single Person Mode';
                    badge.className = 'px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase tracking-wider';
                }
                if (addMugshotBtn) addMugshotBtn.classList.add('hidden');
                if (addSuspectBtn) addSuspectBtn.classList.add('hidden');
            }
        }

        function addMugshotRow() {
            mugshotCount++;
            const container = document.getElementById('mugshotsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `mugshotCard-${mugshotCount}`;
            card.className = 'space-y-3 pt-5 border-t border-slate-200';
            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-slate-700 font-mono-code uppercase">Suspect #${mugshotCount} Mugshot Photos</div>
                    <button type="button" onclick="removeMugshotRow(${mugshotCount})" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold rounded-lg transition-colors border border-red-200 cursor-pointer">
                        &times; Remove Row
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-blue-600 text-white uppercase">Angle 1</span>
                            <span class="text-[10px] font-bold text-slate-500 font-mono-code">Front View (0&deg;)</span>
                        </div>
                        <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('front-${mugshotCount}')">
                            <img id="preview-front-${mugshotCount}" class="hidden w-full h-full object-cover" alt="Front Mugshot">
                            <div id="placeholder-front-${mugshotCount}" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="text-[10px] font-bold">Upload Front Photo</span>
                                <span class="text-[9px] text-slate-400">Full Face Direct</span>
                            </div>
                        </div>
                        <input type="file" id="file-front-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'front-${mugshotCount}')">
                        <button type="button" onclick="triggerMugshotUpload('front-${mugshotCount}')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                            Choose Front Photo
                        </button>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 2</span>
                            <span class="text-[10px] font-bold text-slate-500 font-mono-code">Left Profile (90&deg;)</span>
                        </div>
                        <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('left-profile-${mugshotCount}')">
                            <img id="preview-left-profile-${mugshotCount}" class="hidden w-full h-full object-cover" alt="Left Profile Mugshot">
                            <div id="placeholder-left-profile-${mugshotCount}" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span class="text-[10px] font-bold">Upload Left Profile</span>
                                <span class="text-[9px] text-slate-400">90&deg; Full Left Side</span>
                            </div>
                        </div>
                        <input type="file" id="file-left-profile-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'left-profile-${mugshotCount}')">
                        <button type="button" onclick="triggerMugshotUpload('left-profile-${mugshotCount}')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                            Choose Left Profile
                        </button>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-blue-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 3</span>
                            <span class="text-[10px] font-bold text-slate-500 font-mono-code">Right Profile (90&deg;)</span>
                        </div>
                        <div class="relative w-full h-36 bg-slate-200/80 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden group cursor-pointer" onclick="triggerMugshotUpload('right-profile-${mugshotCount}')">
                            <img id="preview-right-profile-${mugshotCount}" class="hidden w-full h-full object-cover" alt="Right Profile Mugshot">
                            <div id="placeholder-right-profile-${mugshotCount}" class="flex flex-col items-center justify-center p-2 text-slate-400 group-hover:text-blue-600 transition-colors">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-[10px] font-bold">Upload Right Profile</span>
                                <span class="text-[9px] text-slate-400">90&deg; Full Right Side</span>
                            </div>
                        </div>
                        <input type="file" id="file-right-profile-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'right-profile-${mugshotCount}')">
                        <button type="button" onclick="triggerMugshotUpload('right-profile-${mugshotCount}')" class="w-full py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-[11px] font-bold rounded-xl shadow-2xs transition-colors">
                            Choose Right Profile
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function removeMugshotRow(id) {
            const card = document.getElementById(`mugshotCard-${id}`);
            if (card) card.remove();
        }

        function addSuspectDetailsRow() {
            suspectCount++;
            const container = document.getElementById('suspectsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `suspectCard-${suspectCount}`;
            card.className = 'space-y-6 pt-6 border-t-2 border-slate-200/80';
            card.innerHTML = `
                <div class="flex items-center justify-between bg-slate-100 p-3.5 rounded-xl border border-slate-200">
                    <span class="font-extrabold text-slate-900 text-sm font-mono-code uppercase flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-bold">${suspectCount}</span>
                        <span>Suspect #${suspectCount} Personal Booking Record</span>
                    </span>
                    <button type="button" onclick="removeSuspectRow(${suspectCount})" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors border-none cursor-pointer">
                        &times; Remove Suspect #${suspectCount}
                    </button>
                </div>

                <!-- SUB-HEADER 3A: LEGAL IDENTITY & ORIGIN -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                        <span>3A. Legal Identity &amp; Origin Details (Suspect #${suspectCount})</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Raid Incident Reference</span>
                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200 px-2 py-0.5 rounded font-bold">READONLY</span>
                            </label>
                            <input type="text" value="RAID-2026-0419" readonly class="w-full px-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-800 cursor-not-allowed select-none shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Suspect Full Legal Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" placeholder="e.g. Ama Serwaa" required class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Alias / Street Name
                            </label>
                            <input type="text" placeholder="e.g. Queen Bee" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Ghana Card # <span class="text-red-500">*</span>
                            </label>
                            <input type="text" placeholder="GHA-0000000-0" required class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Gender</label>
                            <select class="w-full px-3 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-red-600">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other / Non-Binary</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Date of Birth</label>
                            <input type="date" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Age</label>
                            <input type="number" placeholder="Age" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 focus:outline-none focus:border-red-600">
                        </div>
                    </div>
                </div>

                <!-- SUB-HEADER 3B: MARITAL & RESIDENTIAL RECORDS -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <span>3B. Residential Address &amp; Family Records (Suspect #${suspectCount})</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Place of Birth / Hometown</label>
                            <input type="text" placeholder="e.g. Kumasi, Ashanti Region" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Contact Phone Number</label>
                            <input type="text" placeholder="e.g. +233 24 555 0192" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Residential Address / GPS</label>
                            <input type="text" placeholder="e.g. H/No 14 East Legon, Accra" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                    </div>
                </div>

                <!-- SUB-HEADER 3C: Offence CHARGES & CUSTODY ALLOCATION -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-red-600 uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                        <span>3C. Offence Charges &amp; Custody Holding Facility (Suspect #${suspectCount})</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Place of Raid Scene &amp; Offense</label>
                            <input type="text" placeholder="e.g. East Legon Sector 4" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Offence / Charge Description</label>
                            <input type="text" placeholder="e.g. Narcotics Trafficking &amp; Illegal Firearm" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Assigned Custody Holding Facility</label>
                            <input type="text" placeholder="e.g. NACOC Holding Cell Sector B" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-red-600 shadow-2xs">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function removeSuspectRow(id) {
            const card = document.getElementById(`suspectCard-${id}`);
            if (card) card.remove();
        }

        function toggleSidebarEventMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('sidebarEventMenu');
            const chevron = document.getElementById('sidebarEventChevron');
            if (menu) menu.classList.toggle('hidden');
            if (chevron) chevron.classList.toggle('rotate-180');
        }

        function triggerMugshotUpload(angleKey) {
            const input = document.getElementById(`file-${angleKey}`);
            if (input) input.click();
        }

        let seizedExhibitFiles = [];

        function triggerSeizurePhotoUpload() {
            const input = document.getElementById('fileSeizurePhoto');
            if (input) input.click();
        }

        function previewSeizurePhotos(event) {
            const files = Array.from(event.target.files);
            if (!files || files.length === 0) return;

            files.forEach(file => {
                seizedExhibitFiles.push(file);
            });

            renderSeizurePhotosGrid();
        }

        function renderSeizurePhotosGrid() {
            const grid = document.getElementById('seizurePhotosGrid');
            const countBadge = document.getElementById('seizurePhotosCount');
            if (!grid) return;

            grid.innerHTML = '';

            if (seizedExhibitFiles.length === 0) {
                grid.classList.add('hidden');
                if (countBadge) countBadge.classList.add('hidden');
                return;
            }

            grid.classList.remove('hidden');
            if (countBadge) {
                countBadge.classList.remove('hidden');
                countBadge.innerText = `${seizedExhibitFiles.length} Photo${seizedExhibitFiles.length > 1 ? 's' : ''} Uploaded`;
            }

            seizedExhibitFiles.forEach((file, index) => {
                const reader = new FileReader();
                const card = document.createElement('div');
                card.className = 'relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-2xs h-24 flex items-center justify-center';

                reader.onload = function(e) {
                    card.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover" alt="Exhibit Photo ${index + 1}">
                        <div class="absolute inset-0 bg-slate-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-1">
                            <button type="button" onclick="removeSingleSeizurePhoto(${index}, event)" class="p-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-xs" title="Delete Photo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-slate-900/80 text-white text-[9px] font-mono-code font-bold rounded">#${index + 1}</span>
                    `;
                };

                reader.readAsDataURL(file);
                grid.appendChild(card);
            });
        }

        function removeSingleSeizurePhoto(index, e) {
            if (e) e.stopPropagation();
            seizedExhibitFiles.splice(index, 1);
            renderSeizurePhotosGrid();
        }

        function previewMugshot(event, angleKey) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(`preview-${angleKey}`);
                    const placeholder = document.getElementById(`placeholder-${angleKey}`);
                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('hidden');
                    }
                    if (placeholder) {
                        placeholder.classList.add('hidden');
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        function processNewArrested() {
            const ref = document.getElementById('newArrestedRaidRef').value;
            const name = document.getElementById('newArrestedName').value || 'Unnamed Suspect';
            const alias = document.getElementById('newArrestedAlias').value;
            const card = document.getElementById('newArrestedID').value || 'Unspecified Card';
            const gender = document.getElementById('newArrestedGender').value || 'Unspecified';
            const dob = document.getElementById('newArrestedDOB').value || 'N/A';
            const age = document.getElementById('newArrestedAge').value || 'N/A';
            const pob = document.getElementById('newArrestedPOB').value || 'N/A';
            const phone = document.getElementById('newArrestedPhone').value || 'N/A';
            const address = document.getElementById('newArrestedAddress').value || 'Unspecified Address';
            const marital = document.getElementById('newArrestedMarital').value || 'Unspecified';
            const hasChildren = document.getElementById('hasChildrenCheckbox').checked;
            const childrenVal = hasChildren ? document.getElementById('newArrestedChildren').value : '0';
            const childrenText = hasChildren ? `${childrenVal} Kids` : 'No Children';
            const Offence = document.getElementById('newArrestedOffense').value || 'Under Investigation';
            const raidLocation = document.getElementById('newArrestedRaidLocation').value || 'Unspecified Scene';
            const facility = document.getElementById('newArrestedFacility').value || 'NACOC Holding Cell';

            let partnerInfoHtml = '';
            if (marital === 'Married') {
                const spouseName = document.getElementById('newArrestedSpouseName').value || 'N/A';
                const spousePhone = document.getElementById('newArrestedSpousePhone').value || 'N/A';
                partnerInfoHtml = `<div class="text-[10px] text-red-600 font-semibold">Spouse: ${spouseName} (${spousePhone})</div>`;
            } else if (marital === 'Dating / In Relationship') {
                const partnerName = document.getElementById('newArrestedPartnerName').value || 'N/A';
                const partnerPhone = document.getElementById('newArrestedPartnerPhone').value || 'N/A';
                partnerInfoHtml = `<div class="text-[10px] text-pink-600 font-semibold">Partner: ${partnerName} (${partnerPhone})</div>`;
            }

            const hasSeizures = document.getElementById('hasSeizuresCheckbox').checked;
            let seizureHtml = '';
            if (hasSeizures) {
                const sName = document.getElementById('newSeizureName').value || 'Contraband Item';
                const sQty = document.getElementById('newSeizureQuantity').value || '1';
                const sUnit = document.getElementById('newSeizureUnit').value || 'Units';
                seizureHtml = `<div class="mt-1 text-[10px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded inline-block border border-emerald-200">Seized: ${sQty} ${sUnit} ${sName}</div>`;
            }

            const tbody = document.getElementById('ArrestedRosterTableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-slate-50 transition-colors';
            const ArrestedCode = 'ARR-2026-' + Math.floor(100 + Math.random() * 900);
            newRow.innerHTML = `
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900 font-mono-code">${ArrestedCode}</div>
                    <div class="text-[10px] text-slate-400 font-mono-code">${new Date().toISOString().slice(0, 10)}</div>
                </td>
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900 text-sm">${name} ${alias ? `"${alias}"` : ''}</div>
                    <div class="text-[10px] text-slate-500 font-mono-code">Card: ${card} &bull; ${gender} (${age})</div>
                    <div class="text-[10px] text-slate-600 font-mono-code">DOB: ${dob} &bull; Tel: ${phone}</div>
                </td>
                <td class="py-4 px-5">
                    <div class="font-semibold text-slate-800">${address}</div>
                    <div class="text-[10px] text-slate-500">Born: ${pob} &bull; Status: ${marital} (${childrenText})</div>
                    ${partnerInfoHtml}
                </td>
                <td class="py-4 px-5 max-w-xs">
                    <div class="font-semibold text-slate-800">${ref} (${raidLocation})</div>
                    <div class="text-[10px] text-red-600 truncate font-semibold">${offense}</div>
                    ${seizureHtml}
                </td>
                <td class="py-4 px-5">
                    <div class="text-emerald-700 font-bold">Biometric Log Active</div>
                    <div class="text-[10px] text-slate-400">${facility}</div>
                </td>
                <td class="py-4 px-5">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                        In NACOC Custody
                    </span>
                </td>
                <td class="py-4 px-5 text-right space-x-2">
                    <button onclick="alert('Printing Official Arrested Docket Sheet...')" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-[11px] font-bold shadow-2xs">Print Sheet</button>
                    <button onclick="alert('Transferring suspect docket to Prosecutor...')" class="px-3 py-1.5 bg-red-600 text-white hover:bg-red-700 rounded-xl text-[11px] font-bold shadow-2xs">Transfer Docket</button>
                </td>
            `;

            if (tbody) {
                tbody.insertBefore(newRow, tbody.firstChild);
            }
            alert(`Official Suspect Arrested Docket ${ArrestedCode} successfully locked for ${name}. Suspect in NACOC Custody.`);
        }

        function closeAllDropdowns(exceptMenuId = null) {
            const list = [
                { menu: 'caseEntryTypeDropdownMenu', chevron: 'caseTypeChevron' },
                { menu: 'genderDropdownMenu', chevron: 'genderChevron' },
                { menu: 'nationalityDropdownMenu', chevron: 'nationalityChevron' },
                { menu: 'maritalDropdownMenu', chevron: 'maritalChevron' },
                { menu: 'exhibitDropdownMenu', chevron: 'exhibitChevron' },
                { menu: 'sidebarEventMenu', chevron: 'sidebarEventChevron' }
            ];
            list.forEach(item => {
                if (item.menu !== exceptMenuId) {
                    const m = document.getElementById(item.menu);
                    const c = document.getElementById(item.chevron);
                    if (m) m.classList.add('hidden');
                    if (c) c.classList.remove('rotate-180');
                }
            });
        }

        function toggleCaseEntryTypeDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('caseEntryTypeDropdownMenu');
            const chevron = document.getElementById('caseTypeChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('caseEntryTypeDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectCaseEntryType(val, label, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('caseEntryTypeSelect');
            if (input) input.value = val;
            const labelEl = document.getElementById('selectedCaseTypeLabel');
            if (labelEl) labelEl.innerText = label;
            closeAllDropdowns();
            handleCaseEntryTypeChange();
        }

        function toggleSidebarEventMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('sidebarEventMenu');
            const chevron = document.getElementById('sidebarEventChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('sidebarEventMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function toggleGenderDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('genderDropdownMenu');
            const chevron = document.getElementById('genderChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('genderDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectGender(val, symbol, colorClasses, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedGender');
            if (input) input.value = val;
            const label = document.getElementById('selectedGenderLabel');
            if (label) label.innerText = val;
            const icon = document.getElementById('selectedGenderIcon');
            if (icon) {
                icon.innerText = symbol;
                icon.className = `w-5 h-5 rounded-full ${colorClasses} flex items-center justify-center text-[10px] font-bold`;
            }
            closeAllDropdowns();
        }

        function toggleNationalityDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('nationalityDropdownMenu');
            const chevron = document.getElementById('nationalityChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('nationalityDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                const search = document.getElementById('nationalitySearchInput');
                if (search) setTimeout(() => search.focus(), 50);
            }
        }

        function selectNationality(val, code, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedNationality');
            if (input) input.value = val;
            const label = document.getElementById('selectedNationalityLabel');
            if (label) label.innerText = val;
            const badge = document.getElementById('selectedNationalityBadge');
            if (badge && code) badge.innerText = code;
            closeAllDropdowns();
        }

        function filterNationalities() {
            const query = document.getElementById('nationalitySearchInput').value.toLowerCase();
            const options = document.querySelectorAll('.nat-option');
            options.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                if (text.includes(query)) {
                    opt.style.display = 'flex';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        function toggleChildrenInput() {
            const checkbox = document.getElementById('hasChildrenCheckbox');
            const container = document.getElementById('childrenInputContainer');
            const input = document.getElementById('newArrestedChildren');

            if (checkbox && checkbox.checked) {
                if (container) container.classList.remove('hidden');
                if (input && (!input.value || input.value === '0')) {
                    input.value = '1';
                }
            } else {
                if (container) container.classList.add('hidden');
                if (input) input.value = '0';
            }
        }

        function toggleSeizuresContainer() {
            const chk = document.getElementById('hasSeizuresCheckbox');
            const container = document.getElementById('seizuresDetailsContainer');
            if (chk && container) {
                if (chk.checked) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        }

        function toggleMaritalDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('maritalDropdownMenu');
            const chevron = document.getElementById('maritalChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('maritalDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectMaritalStatus(val, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedMarital');
            if (input) input.value = val;
            const label = document.getElementById('selectedMaritalLabel');
            if (label) {
                label.innerText = val;
                label.className = "font-bold text-slate-900 text-xs";
            }
            closeAllDropdowns();
            handleMaritalChange(val);
        }

        function handleMaritalChange(val) {
            const container = document.getElementById('relationshipDetailsBox');
            const title = document.getElementById('relationshipTitle');
            const subtitle = document.getElementById('relationshipSubtitle');
            const spouseCard = document.getElementById('spouseFieldsCard');
            const partnerCard = document.getElementById('partnerFieldsCard');

            if (!container) return;

            if (val === 'Married') {
                container.classList.remove('hidden');
                if (title) title.innerText = 'Spouse Information (Marriage Record)';
                if (subtitle) subtitle.innerText = 'Mandatory record for suspect legal spouse identity, phone contact, and occupation';
                if (spouseCard) spouseCard.classList.remove('hidden');
                if (partnerCard) partnerCard.classList.add('hidden');
            } else if (val === 'Dating / In Relationship') {
                container.classList.remove('hidden');
                if (title) title.innerText = 'Dating Partner Information (Relationship Record)';
                if (subtitle) subtitle.innerText = 'Record for suspect dating partner full name, phone contact, and residence';
                if (spouseCard) spouseCard.classList.add('hidden');
                if (partnerCard) partnerCard.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function toggleUnitDropdown(e) {
            if (e) e.stopPropagation();
            const panel = document.getElementById('unitListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const hidden  = document.getElementById('newSeizureUnit');
            const display = document.getElementById('unitSearchInput');
            const panel   = document.getElementById('unitListPanel');
            if (hidden)  hidden.value = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
        }

        function filterUnitList() {
            const q = (document.getElementById('unitSearchInput').value || '').toLowerCase();
            filterUnitListByQuery(q);
        }

        function filterUnitListInner() {
            const q = (document.getElementById('unitSearchInner').value || '').toLowerCase();
            filterUnitListByQuery(q);
        }

        function filterUnitListByQuery(q) {
            const items = document.querySelectorAll('#unitList .unit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Close unit panel when clicking outside
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('unitSelectWrapper');
            const panel   = document.getElementById('unitListPanel');
            if (wrapper && panel && !wrapper.contains(e.target)) {
                panel.classList.add('hidden');
            }
        }, true);

        function selectExhibitCategory(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const input   = document.getElementById('newSeizureCategory');
            const display = document.getElementById('exhibitSearchInput');
            const panel   = document.getElementById('exhibitListPanel');
            if (input)   input.value = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
            updateCommodityField(val);
        }

        function filterExhibitList() {
            const q = (document.getElementById('exhibitSearchInput').value || '').toLowerCase();
            filterExhibitListByQuery(q);
        }

        function filterExhibitListInner() {
            const q = (document.getElementById('exhibitSearchInner').value || '').toLowerCase();
            filterExhibitListByQuery(q);
        }

        function filterExhibitListByQuery(q) {
            const items = document.querySelectorAll('#exhibitList .exhibit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Close exhibit panel when clicking outside
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('exhibitSelectWrapper');
            const panel   = document.getElementById('exhibitListPanel');
            if (wrapper && panel && !wrapper.contains(e.target)) {
                panel.classList.add('hidden');
            }
        }, true);

        function updateCommodityField(category) {
            const textWrapper     = document.getElementById('commodityTextWrapper');
            const drugWrapper     = document.getElementById('drugSelectWrapper');
            const currencyWrapper = document.getElementById('currencySelectWrapper');
            const textInput       = document.getElementById('newSeizureName');
            const drugHidden      = document.getElementById('newSeizureNameDrug');
            const currencyHidden  = document.getElementById('newSeizureNameCurrency');
            const drugSearch      = document.getElementById('drugSearchInput');
            const currencySearch  = document.getElementById('currencySearchInput');
            const quantityLabel   = document.getElementById('quantityLabel');

            // Hide all first
            if (textWrapper)     textWrapper.classList.add('hidden');
            if (drugWrapper)     drugWrapper.classList.add('hidden');
            if (currencyWrapper) currencyWrapper.classList.add('hidden');
            if (textInput)       { textInput.removeAttribute('required'); textInput.value = ''; }
            if (drugHidden)      { drugHidden.removeAttribute('required'); drugHidden.value = ''; }
            if (currencyHidden)  { currencyHidden.removeAttribute('required'); currencyHidden.value = ''; }

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Weight';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
                if (drugHidden)  drugHidden.setAttribute('required', 'required');
                if (drugSearch)  { drugSearch.value = ''; drugSearch.placeholder = 'Select drug type...'; }
                filterDrugListByQuery('');
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
                if (currencyHidden)  currencyHidden.setAttribute('required', 'required');
                if (currencySearch)  { currencySearch.value = ''; currencySearch.placeholder = 'Select currency...'; }
                filterCurrencyListByQuery('');
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Weight';
                if (textWrapper) textWrapper.classList.remove('hidden');
                if (textInput)   textInput.setAttribute('required', 'required');
                const placeholders = {
                    'Gold':           'e.g. Gold Bars / Bullion (24K)',
                    'Vehicles':       'e.g. Toyota Hilux (Unregistered)',
                    'Other Exhibits': 'e.g. Illegal Firearms / Devices'
                };
                if (textInput) textInput.placeholder = placeholders[category] || 'e.g. Primary Commodity Name';
            }
        }

        function selectDrugType(val) {
            const hidden  = document.getElementById('newSeizureNameDrug');
            const display = document.getElementById('drugSearchInput');
            const panel   = document.getElementById('drugListPanel');
            if (hidden)  hidden.value  = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
            // sync to the original text input for form processing
            const textInput = document.getElementById('newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDrugList() {
            const q = (document.getElementById('drugSearchInput').value || '').toLowerCase();
            filterDrugListByQuery(q);
        }

        function filterDrugListInner() {
            const q = (document.getElementById('drugSearchInner').value || '').toLowerCase();
            filterDrugListByQuery(q);
        }

        function filterDrugListByQuery(q) {
            const items = document.querySelectorAll('#drugList .drug-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Close drug panel when clicking outside
        document.addEventListener('click', function(e) {
            const drugWrapper = document.getElementById('drugSelectWrapper');
            const panel = document.getElementById('drugListPanel');
            if (drugWrapper && panel && !drugWrapper.contains(e.target)) {
                panel.classList.add('hidden');
            }
        }, true);

        function selectCurrencyType(val) {
            const hidden   = document.getElementById('newSeizureNameCurrency');
            const display  = document.getElementById('currencySearchInput');
            const panel    = document.getElementById('currencyListPanel');
            if (hidden)  hidden.value  = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
            // sync to the original text input for form processing
            const textInput = document.getElementById('newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterCurrencyList() {
            const q = (document.getElementById('currencySearchInput').value || '').toLowerCase();
            filterCurrencyListByQuery(q);
        }

        function filterCurrencyListInner() {
            const q = (document.getElementById('currencySearchInner').value || '').toLowerCase();
            filterCurrencyListByQuery(q);
        }

        function filterCurrencyListByQuery(q) {
            const items = document.querySelectorAll('#currencyList .currency-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Close currency panel when clicking outside
        document.addEventListener('click', function(e) {
            const currencyWrapper = document.getElementById('currencySelectWrapper');
            const panel = document.getElementById('currencyListPanel');
            if (currencyWrapper && panel && !currencyWrapper.contains(e.target)) {
                panel.classList.add('hidden');
            }
        }, true);



        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const caseMenu = document.getElementById('caseEntryTypeDropdownMenu');
            const caseTrigger = document.getElementById('caseEntryTypeDropdownTrigger');
            if (caseMenu && !caseMenu.classList.contains('hidden')) {
                if (!caseMenu.contains(e.target) && (!caseTrigger || !caseTrigger.contains(e.target))) {
                    caseMenu.classList.add('hidden');
                    const cChevron = document.getElementById('caseTypeChevron');
                    if (cChevron) cChevron.classList.remove('rotate-180');
                }
            }
            const exhibitMenu = document.getElementById('exhibitDropdownMenu');
            const exhibitTrigger = document.getElementById('exhibitDropdownTrigger');
            if (exhibitMenu && !exhibitMenu.classList.contains('hidden')) {
                if (!exhibitMenu.contains(e.target) && (!exhibitTrigger || !exhibitTrigger.contains(e.target))) {
                    exhibitMenu.classList.add('hidden');
                    const eChevron = document.getElementById('exhibitChevron');
                    if (eChevron) eChevron.classList.remove('rotate-180');
                }
            }
            const natContainer = document.getElementById('nationalityDropdownMenu');
            const natTrigger = document.getElementById('natDropdownTrigger');
            if (natContainer && !natContainer.classList.contains('hidden')) {
                if (!natContainer.contains(e.target) && (!natTrigger || !natTrigger.contains(e.target))) {
                    natContainer.classList.add('hidden');
                    const chevron = document.getElementById('nationalityChevron');
                    if (chevron) chevron.classList.remove('rotate-180');
                }
            }

            const genderMenu = document.getElementById('genderDropdownMenu');
            const genderTrigger = document.getElementById('genderDropdownTrigger');
            if (genderMenu && !genderMenu.classList.contains('hidden')) {
                if (!genderMenu.contains(e.target) && (!genderTrigger || !genderTrigger.contains(e.target))) {
                    genderMenu.classList.add('hidden');
                    const gChevron = document.getElementById('genderChevron');
                    if (gChevron) gChevron.classList.remove('rotate-180');
                }
            }

            const maritalMenu = document.getElementById('maritalDropdownMenu');
            const maritalTrigger = document.getElementById('maritalDropdownTrigger');
            if (maritalMenu && !maritalMenu.classList.contains('hidden')) {
                if (!maritalMenu.contains(e.target) && (!maritalTrigger || !maritalTrigger.contains(e.target))) {
                    maritalMenu.classList.add('hidden');
                    const mChevron = document.getElementById('maritalChevron');
                    if (mChevron) mChevron.classList.remove('rotate-180');
                }
            }

            const sidebarMenu = document.getElementById('sidebarEventMenu');
            const sidebarTrigger = document.getElementById('sidebarEventTrigger');
            if (sidebarMenu && !sidebarMenu.classList.contains('hidden')) {
                if (!sidebarMenu.contains(e.target) && (!sidebarTrigger || !sidebarTrigger.contains(e.target))) {
                    sidebarMenu.classList.add('hidden');
                    const sChevron = document.getElementById('sidebarEventChevron');
                    if (sChevron) sChevron.classList.remove('rotate-180');
                }
            }
        });

        function calculateAgeFromDOB() {
            const dobInput = document.getElementById('newArrestedDOB');
            const ageInput = document.getElementById('newArrestedAge');
            if (!dobInput || !ageInput) return;

            const dobVal = dobInput.value;
            if (!dobVal) {
                ageInput.value = '';
                return;
            }

            const dob = new Date(dobVal);
            if (isNaN(dob.getTime())) {
                ageInput.value = '';
                return;
            }

            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            if (age >= 0) {
                ageInput.value = age;
            } else {
                ageInput.value = '';
            }
        }

        // Auto-read URL query ref parameter & calculate initial age if DOB exists + Setup Drag & Drop
        document.addEventListener('DOMContentLoaded', function() {
            calculateAgeFromDOB();
            const urlParams = new URLSearchParams(window.location.search);
            const refParam = urlParams.get('ref');
            if (refParam) {
                const raidRefInput = document.getElementById('newArrestedRaidRef');
                if (raidRefInput) raidRefInput.value = refParam;
            }

            const dropzone = document.getElementById('seizurePhotoDropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('border-red-600', 'bg-red-50/50');
                    }, false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('border-red-600', 'bg-red-50/50');
                    }, false);
                });
                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = Array.from(dt.files || []);
                    files.forEach(file => {
                        if (file.type.startsWith('image/')) {
                            seizedExhibitFiles.push(file);
                        }
                    });
                    renderSeizurePhotosGrid();
                }, false);
            }
        });
    </script>
</body>
</html>
