<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Officer Registration | NACOC Forensic Directorate</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
        .white-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        /* Toggle Switch Animation */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #2563EB;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #2563EB;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between bg-slate-50 text-slate-900 antialiased selection:bg-blue-600 selection:text-white transition-opacity duration-300 opacity-100">

    <!-- TOP SYSTEM STATUS BAR -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white shadow-2xs">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white shadow-md shadow-blue-600/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-0.5">NACOC FORENSIC DIRECTORATE</div>
                <div class="text-[10px] text-blue-600 font-semibold uppercase tracking-wider">NATIONAL NARCOTICS CONTROL COMMISSION</div>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-semibold text-slate-800" id="liveClock">2026-09-05 00:00:00 UTC</div>
                <div class="text-[10px] font-mono-code text-blue-600 font-bold">SECURE REGISTRATION</div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold font-mono-code bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                SYSTEM ONLINE
            </span>
        </div>
    </header>

    <!-- MAIN REGISTRATION CONTAINER -->
    <main class="flex-1 flex items-center justify-center p-4 my-8">
        <div class="w-full max-w-3xl space-y-6">
            
            <!-- BRAND HEADER INSIGNIA -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/30 mb-1">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create Officer Account</h1>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">
                    Register your service credentials for NACOC Forensic Directorate authorization
                </p>
            </div>

            <!-- MAIN REGISTRATION FORM CARD -->
            <div class="white-card p-6 sm:p-8 rounded-2xl space-y-5">
                
                @if ($errors->any())
                    <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-800 flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form id="registerForm" method="POST" action="{{ url('/register') }}" onsubmit="handleRegisterSubmit(event)">
                    @csrf
                    <div class="space-y-4">
                        
                        <!-- ROW 1: FULL NAME & USERNAME -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Full Name <span class="text-red-600 font-bold">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="fullname" 
                                        name="fullname" 
                                        required 
                                        oninput="checkPasswordStrength(document.getElementById('password').value)"
                                        placeholder="e.g. Insp. Samuel Mensah" 
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Username <span class="text-red-600 font-bold">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <span class="text-xs font-bold font-mono-code text-slate-400">@</span>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="username" 
                                        name="username" 
                                        required 
                                        oninput="checkPasswordStrength(document.getElementById('password').value)"
                                        placeholder="e.g. smensah" 
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: SERVICE ID NUMBER & PHONE NUMBER -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Service ID Number <span class="text-red-600 font-bold">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="service_id" 
                                        name="service_id" 
                                        required 
                                        placeholder="e.g. NFC-89201" 
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Phone Number <span class="text-red-600 font-bold">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </div>
                                    <input 
                                        type="tel" 
                                        id="phone_number" 
                                        name="phone_number" 
                                        required 
                                        maxlength="10"
                                        minlength="10"
                                        pattern="[0-9]{10}"
                                        inputmode="numeric"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                        placeholder="e.g. 0200000000" 
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- TOGGLE SWITCH: USER IS STAFF -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">User is Staff Member</div>
                                    <div class="text-[10px] text-slate-500">Toggle ON to assign field operational staff clearance</div>
                                </div>
                            </div>

                            <!-- TOGGLE SWITCH BUTTON -->
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="isStaffToggle" name="is_staff" checked onchange="toggleStaffStatus(this)" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        @php
                            $defaultUnit = isset($units) ? $units->first() : null;
                            $defaultUnitName = $defaultUnit ? $defaultUnit->name : 'Crime Scene Support';
                            $defaultHeadDisplay = isset($registeredHeads) && isset($registeredHeads[$defaultUnitName]) 
                                ? $registeredHeads[$defaultUnitName] 
                                : 'Your unit head has not registered yet';
                        @endphp

                        <!-- ROW 3: UNITS / DIVISION & ASSIGNED UNIT HEAD (READ-ONLY) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="unitsGrid">
                            <!-- UNITS (CUSTOM SELECT2-STYLE SEARCHABLE DROPDOWN) -->
                            <div id="unitsDropdownWrapper" class="col-span-1">
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Units / Division <span class="text-red-600 font-bold">*</span></label>
                                
                                <!-- SELECT2 CUSTOM DROPDOWN CONTAINER -->
                                <div class="relative" id="select2Container">
                                    <!-- Trigger Button -->
                                    <button 
                                        type="button" 
                                        id="select2Trigger" 
                                        onclick="toggleSelect2Dropdown()" 
                                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 flex items-center justify-between focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                    >
                                        <div class="flex items-center space-x-2 truncate">
                                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V7m5 5v6m-5-6v6"/></svg>
                                            <span id="selectedUnitText" class="font-semibold text-slate-900">{{ $defaultUnitName }}</span>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" id="select2Arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <input type="hidden" id="selectedUnitInput" name="unit" value="{{ $defaultUnitName }}">

                                    <!-- Select2 Searchable Dropdown Popup -->
                                    <div id="select2Menu" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-slate-300 rounded-xl shadow-xl z-50 overflow-hidden">
                                        
                                        <!-- Search Input -->
                                        <div class="p-2 border-b border-slate-200 bg-slate-50">
                                            <div class="relative">
                                                <input 
                                                    type="text" 
                                                    id="select2SearchInput" 
                                                    onkeyup="filterSelect2Options()" 
                                                    placeholder="Search unit name..." 
                                                    class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 font-mono-code"
                                                >
                                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            </div>
                                        </div>

                                        <!-- Option List (Fetched dynamically from PostgreSQL) -->
                                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 py-1" id="select2OptionsList">
                                            @if(isset($units) && count($units) > 0)
                                                @foreach($units as $u)
                                                    <div onclick="selectUnitOption('{{ $u->name }}')" class="select2-option px-3.5 py-2.5 hover:bg-blue-50 text-xs font-semibold text-slate-800 cursor-pointer flex items-center justify-between">
                                                        <span>{{ $u->name }}</span>
                                                        <span class="text-[10px] text-blue-600 font-mono-code font-bold">{{ $u->category }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div onclick="selectUnitOption('Crime Scene Support')" class="select2-option px-3.5 py-2.5 hover:bg-blue-50 text-xs font-semibold text-slate-800 cursor-pointer flex items-center justify-between">
                                                    <span>Crime Scene Support</span>
                                                    <span class="text-[10px] text-blue-600 font-mono-code font-bold">FIELD OPS</span>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- ASSIGNED UNIT HEAD (ONLY SHOWN WHEN USER IS STAFF MEMBER) -->
                            <div id="unitHeadContainer" class="col-span-1 transition-all duration-200">
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Assigned Unit Head</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="unitHeadInput" 
                                        name="unit_head" 
                                        readonly 
                                        value="{{ $defaultHeadDisplay }}" 
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-800 font-semibold font-mono-code cursor-not-allowed select-none focus:outline-none transition-all"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- PASSWORD & CONFIRM PASSWORD WITH STRENGTH BAR -->
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Password <span class="text-red-600 font-bold">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="password" 
                                            id="password" 
                                            name="password" 
                                            required 
                                            oninput="checkPasswordStrength(this.value); checkPasswordMatch();"
                                            placeholder="Min. 8 characters" 
                                            class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                        >
                                        <button 
                                            type="button" 
                                            onclick="toggleFieldPassword('password')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Confirm Password <span class="text-red-600 font-bold">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="password_confirmation" 
                                            required 
                                            oninput="checkPasswordMatch()"
                                            placeholder="Re-enter password" 
                                            class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                        >
                                        <button 
                                            type="button" 
                                            onclick="toggleFieldPassword('confirm_password')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                    <div id="matchNotice" class="hidden text-[10px] font-mono-code mt-1 text-red-600 font-bold">Passwords do not match</div>
                                </div>
                            </div>

                            <!-- PASSWORD STRENGTH PROGRESS BAR & CRITERIA CHECKLIST -->
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                <div class="flex items-center justify-between text-[11px] font-mono-code">
                                    <span class="text-slate-600">Password Strength:</span>
                                    <span id="strengthText" class="font-bold text-slate-400">Enter Password</span>
                                </div>

                                <!-- PROGRESS BAR -->
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div id="strengthBar" class="h-1.5 w-0 bg-slate-400 transition-all duration-300 rounded-full"></div>
                                </div>

                                <!-- REQUIREMENTS NOTICE TEXT -->
                                <p class="text-[11px] text-slate-500 leading-tight">
                                    Your password must be <strong class="text-slate-700">at least 8 characters</strong> and include a <strong class="text-slate-700">number, uppercase letter, and symbol</strong>, without containing your <strong class="text-red-600">name or username</strong>.
                                </p>

                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 pt-1 text-[10px] font-mono-code">
                                    <div id="reqLen" class="px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">8+ Chars</div>
                                    <div id="reqCaps" class="px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Caps (A-Z)</div>
                                    <div id="reqNum" class="px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Num (0-9)</div>
                                    <div id="reqSym" class="px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Symbol (@#$)</div>
                                    <div id="reqNoUser" class="col-span-2 sm:col-span-1 px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">No Name/User</div>
                                </div>
                            </div>
                        </div>

                        <!-- TERMS CHECKBOX -->
                        <div class="pt-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="termsCheckbox" name="terms" required class="w-4 h-4 rounded bg-slate-100 border-slate-300 text-blue-600 focus:ring-blue-600">
                                <span class="text-xs text-slate-700 font-medium">I agree to NACOC Security Policy &amp; Confidentiality Mandate <span class="text-red-600 font-bold">*</span></span>
                            </label>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button 
                            type="submit" 
                            id="regSubmitBtn" 
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-blue-600/25 border border-blue-500 transition-all hover:scale-[1.01] flex items-center justify-center space-x-2 cursor-pointer mt-2"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            <span id="regBtnText">Create Service Account</span>
                        </button>

                        <!-- LOGIN LINK -->
                        <div class="text-center pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-600">
                                Already have an account? 
                                <a href="{{ url('/login') }}" onclick="navigateWithFade(event, '{{ url('/login') }}')" class="font-bold text-blue-600 hover:text-blue-700 hover:underline transition-colors">Sign In</a>
                            </p>
                        </div>

                    </div>
                </form>

            </div>

            <!-- SECURITY BADGE FOOTER -->
            <div class="text-center text-[11px] text-slate-500 font-mono-code">
                NACOC FORENSIC DIRECTORATE &bull; HQ ACCRA, GHANA<br>
                <span>ISO/IEC 17025 ACCREDITED LAW ENFORCEMENT LAB</span>
            </div>

        </div>
    </main>

    <!-- FOOTER BAR -->
    <footer class="w-full px-6 py-3 border-t border-slate-200 bg-white flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-500 font-mono-code gap-2">
        <div>
            Official Property of the Narcotics Control Commission, Ghana
        </div>
        <div class="flex items-center space-x-4">
            <a href="#" class="hover:text-slate-800">Security Policy</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-slate-800">Chain of Custody Spec</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-slate-800">IT Support Desk</a>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Password visibility toggle helper
        function toggleFieldPassword(fieldId) {
            const input = document.getElementById(fieldId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            }
        }

        // Live Password Strength Checker
        function checkPasswordStrength(val) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');

            const reqLen = document.getElementById('reqLen');
            const reqCaps = document.getElementById('reqCaps');
            const reqNum = document.getElementById('reqNum');
            const reqSym = document.getElementById('reqSym');
            const reqNoUser = document.getElementById('reqNoUser');

            const fullnameVal = document.getElementById('fullname') ? document.getElementById('fullname').value.toLowerCase().trim() : '';
            const usernameVal = document.getElementById('username') ? document.getElementById('username').value.toLowerCase().trim() : '';
            const lowerPassword = val.toLowerCase();

            const hasLen = val.length >= 8;
            const hasCaps = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSym = /[^A-Za-z0-9]/.test(val);

            // Check if password contains username or any part of full name (word >= 3 chars)
            let containsUserOrName = false;
            if (usernameVal.length >= 3 && lowerPassword.includes(usernameVal)) {
                containsUserOrName = true;
            }
            if (fullnameVal.length >= 3) {
                const nameParts = fullnameVal.split(/\s+/);
                for (let part of nameParts) {
                    if (part.length >= 3 && lowerPassword.includes(part)) {
                        containsUserOrName = true;
                        break;
                    }
                }
            }
            const noUser = !containsUserOrName;

            // Update Requirement Badges
            updateBadge(reqLen, hasLen);
            updateBadge(reqCaps, hasCaps);
            updateBadge(reqNum, hasNum);
            updateBadge(reqSym, hasSym);

            if (reqNoUser) {
                if (val.length > 0 && containsUserOrName) {
                    reqNoUser.className = 'col-span-2 sm:col-span-1 px-2 py-0.5 rounded bg-red-100 text-red-700 font-bold text-center border border-red-300 transition-colors';
                } else if (val.length > 0 && noUser) {
                    reqNoUser.className = 'col-span-2 sm:col-span-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold text-center border border-emerald-300 transition-colors';
                } else {
                    reqNoUser.className = 'col-span-2 sm:col-span-1 px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors';
                }
            }

            if (val.length === 0) {
                bar.style.width = '0%';
                bar.className = 'h-1.5 w-0 bg-slate-400 transition-all duration-300 rounded-full';
                text.innerText = 'Enter Password';
                text.className = 'font-bold text-slate-400';
                return;
            }

            if (containsUserOrName) {
                bar.style.width = '20%';
                bar.className = 'h-1.5 bg-red-600 transition-all duration-300 rounded-full';
                text.innerText = 'Must NOT contain Name/Username';
                text.className = 'font-bold text-red-600';
                return;
            }

            let score = 0;
            if (hasLen) score++;
            if (hasCaps) score++;
            if (hasNum) score++;
            if (hasSym) score++;

            if (score <= 1) {
                bar.style.width = '25%';
                bar.className = 'h-1.5 bg-red-500 transition-all duration-300 rounded-full';
                text.innerText = 'Weak';
                text.className = 'font-bold text-red-600';
            } else if (score === 2) {
                bar.style.width = '50%';
                bar.className = 'h-1.5 bg-amber-500 transition-all duration-300 rounded-full';
                text.innerText = 'Moderate';
                text.className = 'font-bold text-amber-600';
            } else if (score === 3) {
                bar.style.width = '75%';
                bar.className = 'h-1.5 bg-blue-500 transition-all duration-300 rounded-full';
                text.innerText = 'Good';
                text.className = 'font-bold text-blue-600';
            } else if (score === 4) {
                bar.style.width = '100%';
                bar.className = 'h-1.5 bg-emerald-500 transition-all duration-300 rounded-full';
                text.innerText = 'Strong & Secure';
                text.className = 'font-bold text-emerald-600';
            }
        }

        function updateBadge(elem, passed) {
            if (!elem) return;
            if (passed) {
                elem.className = 'px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold text-center border border-emerald-300 transition-colors';
            } else {
                elem.className = 'px-2 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors';
            }
        }

        // Live Password Match Checker
        function checkPasswordMatch() {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            const notice = document.getElementById('matchNotice');

            if (!notice) return;
            if (p2.length > 0 && p1 !== p2) {
                notice.classList.remove('hidden');
            } else {
                notice.classList.add('hidden');
            }
        }

        // Page transition fade-out
        function navigateWithFade(e, url) {
            e.preventDefault();
            document.body.classList.remove('opacity-100');
            document.body.classList.add('opacity-0');
            setTimeout(() => {
                window.location.href = url;
            }, 250);
        }

        // Live UTC Clock
        function updateClock() {
            const now = new Date();
            const year = now.getUTCFullYear();
            const month = String(now.getUTCMonth() + 1).padStart(2, '0');
            const day = String(now.getUTCDate()).padStart(2, '0');
            const hours = String(now.getUTCHours()).padStart(2, '0');
            const minutes = String(now.getUTCMinutes()).padStart(2, '0');
            const seconds = String(now.getUTCSeconds()).padStart(2, '0');
            const clockElem = document.getElementById('liveClock');
            if (clockElem) {
                clockElem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds} UTC`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Dynamic Unit Head Mapping fetched from PostgreSQL database (registered unit heads)
        const registeredHeadsMap = @json($registeredHeads ?? []);

        function updateUnitHeadDisplay() {
            const isStaffChecked = document.getElementById('isStaffToggle') ? document.getElementById('isStaffToggle').checked : false;
            const currentUnit = document.getElementById('selectedUnitInput') ? document.getElementById('selectedUnitInput').value : 'Crime Scene Support';
            const unitHeadInput = document.getElementById('unitHeadInput');
            const unitHeadContainer = document.getElementById('unitHeadContainer');
            const unitsDropdownWrapper = document.getElementById('unitsDropdownWrapper');

            if (!unitHeadInput || !unitHeadContainer) return;

            if (isStaffChecked) {
                const headName = registeredHeadsMap[currentUnit] || 'Your unit head has not registered yet';
                unitHeadInput.value = headName;
                unitHeadContainer.classList.remove('hidden');
                if (unitsDropdownWrapper) {
                    unitsDropdownWrapper.className = 'col-span-1';
                }
            } else {
                unitHeadContainer.classList.add('hidden');
                if (unitsDropdownWrapper) {
                    unitsDropdownWrapper.className = 'col-span-1 sm:col-span-2';
                }
            }
        }

        // Staff Toggle Change Handler
        function toggleStaffStatus(checkbox) {
            updateUnitHeadDisplay();
        }

        // Select2 Dropdown Toggle
        function toggleSelect2Dropdown() {
            const menu = document.getElementById('select2Menu');
            const arrow = document.getElementById('select2Arrow');
            const searchInput = document.getElementById('select2SearchInput');

            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
                setTimeout(() => searchInput.focus(), 100);
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        // Filter Select2 Options Live
        function filterSelect2Options() {
            const query = document.getElementById('select2SearchInput').value.toLowerCase().trim();
            const options = document.querySelectorAll('.select2-option');

            options.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                if (text.includes(query)) {
                    opt.style.display = 'flex';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        // Select Unit Option
        function selectUnitOption(val) {
            document.getElementById('selectedUnitText').innerText = val;
            document.getElementById('selectedUnitInput').value = val;

            document.getElementById('select2Menu').classList.add('hidden');
            document.getElementById('select2Arrow').classList.remove('rotate-180');

            updateUnitHeadDisplay();
        }

        document.addEventListener('DOMContentLoaded', updateUnitHeadDisplay);
        updateUnitHeadDisplay();

        // Close Select2 Menu when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('select2Container');
            if (container && !container.contains(e.target)) {
                document.getElementById('select2Menu').classList.add('hidden');
                document.getElementById('select2Arrow').classList.remove('rotate-180');
            }
        });

        // Handle Registration Submission
        function handleRegisterSubmit(e) {
            e.preventDefault();

            // Agreement Checkbox Validation
            const termsChecked = document.getElementById('termsCheckbox') ? document.getElementById('termsCheckbox').checked : false;
            if (!termsChecked) {
                alert("Validation Error: You must agree to the NACOC Security Policy & Confidentiality Mandate before registering.");
                return;
            }

            // 0. Phone number must be exactly 10 digits
            const phoneVal = document.getElementById('phone_number') ? document.getElementById('phone_number').value.trim() : '';
            if (!/^[0-9]{10}$/.test(phoneVal)) {
                alert("Validation Error: Phone Number must be exactly 10 digits (e.g. 0200000000).");
                return;
            }

            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            const fullnameVal = document.getElementById('fullname') ? document.getElementById('fullname').value.toLowerCase().trim() : '';
            const usernameVal = document.getElementById('username') ? document.getElementById('username').value.toLowerCase().trim() : '';
            const lowerPassword = p1.toLowerCase();

            // 1. MUST NOT contain username or name
            let containsUserOrName = false;
            if (usernameVal.length >= 3 && lowerPassword.includes(usernameVal)) {
                containsUserOrName = true;
            }
            if (fullnameVal.length >= 3) {
                const nameParts = fullnameVal.split(/\s+/);
                for (let part of nameParts) {
                    if (part.length >= 3 && lowerPassword.includes(part)) {
                        containsUserOrName = true;
                        break;
                    }
                }
            }

            if (containsUserOrName) {
                alert("Security Error: Your password MUST NOT contain your name or username.");
                return;
            }

            // 2. Check 8+ chars, uppercase, number, symbol
            if (p1.length < 8 || !/[A-Z]/.test(p1) || !/[0-9]/.test(p1) || !/[^A-Za-z0-9]/.test(p1)) {
                alert("Security Error: Password must be at least 8 characters long and contain uppercase letters, numbers, and symbols.");
                return;
            }

            // 3. Confirm password match
            if (p1 !== p2) {
                alert("Passwords do not match. Please re-enter.");
                return;
            }

            const regSubmitBtn = document.getElementById('regSubmitBtn');
            const regBtnText = document.getElementById('regBtnText');

            regSubmitBtn.disabled = true;
            regBtnText.innerHTML = `
                <span class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving to Database...
                </span>
            `;

            document.getElementById('registerForm').submit();
        }
    </script>
</body>
</html>
