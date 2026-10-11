<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Officer Registration | Forensic Inventory Management System (FIMS)</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #BBE7E4;
            color: #0F172A;
            margin: 0;
            padding: 0;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Geometric angled background mimicking the reference image */
        .fims-bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #BBE7E4 0%, #B2E2DF 50%, #C7ECE9 100%);
            overflow: hidden;
            pointer-events: none;
        }

        .fims-bg-facet {
            position: absolute;
            top: 0;
            right: 0;
            width: 75vw;
            height: 100vh;
            background: #CEF0ED;
            clip-path: polygon(45% 0, 100% 0, 100% 100%, 0% 100%);
            opacity: 0.85;
        }

        .fims-bg-facet-2 {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50vw;
            height: 60vh;
            background: #AEE0DC;
            clip-path: polygon(0 40%, 100% 100%, 0 100%);
            opacity: 0.6;
        }

        .ref-input-line {
            border-bottom: 2px solid #CBD5E1;
            transition: all 0.2s ease;
        }

        .ref-input-line:focus-within {
            border-bottom-color: #0D9488;
        }
    </style>
</head>

<body
    class="min-h-screen flex flex-col justify-between antialiased selection:bg-teal-700 selection:text-white transition-opacity duration-300 opacity-100 relative">

    <!-- Geometric Pastel Background -->
    <div class="fims-bg-canvas">
        <div class="fims-bg-facet"></div>
        <div class="fims-bg-facet-2"></div>
    </div>

    <!-- TOP SYSTEM STATUS BAR -->
    <header
        class="w-full px-6 py-3.5 flex items-center justify-between bg-white/95 border-b border-teal-900/10 shadow-sm relative z-10 backdrop-blur-sm">
        <div class="flex items-center space-x-3.5">
            <div
                class="w-11 h-11 rounded-xl bg-white p-1 border border-teal-700/20 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-0.5">
                    FORENSIC INVENTORY MANAGEMENT SYSTEM (FIMS)</div>
                <div class="text-[10px] text-teal-700 font-bold uppercase tracking-wider">OFFICER ENROLLMENT &bull;
                    FORENSICS DEPARTMENT</div>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-bold text-slate-800 font-mono-code" id="liveClock">2026-09-05 00:00:00 UTC
                </div>
                <div class="text-[10px] font-mono-code text-teal-700 font-extrabold">ENROLLMENT PORTAL v4.2</div>
            </div>
        </div>
    </header>

    <!-- MAIN REGISTRATION CONTAINER -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 my-6 relative z-10">
        <div
            class="w-full max-w-5xl bg-white rounded-3xl shadow-[0_24px_60px_-12px_rgba(15,23,42,0.18)] border border-slate-200/80 overflow-hidden flex flex-col md:flex-row">

            <!-- LEFT PANEL: Mint-Teal Curved Wave & Vector Graphic (From Reference Image) -->
            <div
                class="md:w-4/12 bg-gradient-to-br from-teal-600 via-teal-700 to-teal-800 text-white relative p-8 flex flex-col justify-between overflow-hidden">

                <!-- Organic Curved Wave SVG Dividing Edge -->
                <svg class="absolute top-0 bottom-0 right-0 h-full w-20 text-white hidden md:block translate-x-1"
                    viewBox="0 0 100 100" preserveAspectRatio="none" fill="currentColor">
                    <path d="M0,0 C65,25 20,70 100,100 L100,0 Z"></path>
                </svg>

                <!-- Vertical Insignia Typography from Reference Image -->
                <div
                    class="absolute left-6 top-8 bottom-8 flex items-center pointer-events-none select-none opacity-20">
                    <span
                        class="text-5xl font-black tracking-widest uppercase transform -rotate-90 origin-center text-white font-mono-code">FIMS</span>
                </div>

                <!-- Top Left Identity Crest -->
                <div class="relative z-10 flex items-center space-x-3 pl-8">
                    <div class="w-12 h-12 rounded-2xl bg-white p-1.5 shadow-md border border-white/30 shrink-0">
                        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Forensics Emblem"
                            class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="text-[11px] font-black uppercase tracking-widest text-teal-200 font-mono-code">NACOC
                            GHANA</div>
                        <div class="text-xs font-bold text-white">Forensic Department</div>
                    </div>
                </div>

                <!-- Isometric Forensic Evidence Terminal Illustration (Faithful to Reference Image) -->
                <div class="relative z-10 my-auto py-6 flex flex-col items-center justify-center pl-6">
                    <svg viewBox="0 0 320 220" class="w-full max-w-[240px] drop-shadow-2xl" fill="none">
                        <!-- Isometric Tablet Base -->
                        <polygon points="160,35 295,95 160,170 25,110" fill="#E2F7F5" stroke="#FFFFFF"
                            stroke-width="3" />
                        <polygon points="25,110 160,170 160,185 25,125" fill="#99D5CF" />
                        <polygon points="160,170 295,95 295,110 160,185" fill="#75C2BC" />

                        <!-- Tablet Screen Surface -->
                        <polygon points="160,48 280,102 160,160 40,108" fill="#F4FCFB" />

                        <!-- Isometric Forensic Chromatography Bars -->
                        <path d="M75,98 L75,70 L82,67 L82,95 Z" fill="#0D9488" />
                        <path d="M82,67 L88,70 L88,97 L82,95 Z" fill="#042F2E" />

                        <path d="M92,94 L92,58 L99,55 L99,91 Z" fill="#14B8A6" />
                        <path d="M99,55 L105,58 L105,93 L99,91 Z" fill="#0F766E" />

                        <path d="M109,90 L109,48 L116,45 L116,86 Z" fill="#2DD4BF" />
                        <path d="M116,45 L122,48 L122,89 L116,86 Z" fill="#0D9488" />

                        <!-- Forensic Spectroscopy Polyline -->
                        <polyline points="75,90 95,65 110,75 125,50 145,72 165,58" stroke="#0D9488" stroke-width="2.5"
                            fill="none" stroke-linecap="round" />

                        <!-- Chain of Custody Biometric Nodes -->
                        <ellipse cx="205" cy="118" rx="20" ry="11" fill="none"
                            stroke="#0D9488" stroke-width="2" stroke-dasharray="3 2" />
                        <ellipse cx="205" cy="118" rx="12" ry="6.5" fill="none"
                            stroke="#14B8A6" stroke-width="1.8" />
                        <ellipse cx="205" cy="118" rx="4" ry="2" fill="#042F2E" />

                        <!-- Evidence Tag / Document Grid -->
                        <polygon points="195,68 250,92 225,104 170,80" fill="#E6FFFA" stroke="#14B8A6"
                            stroke-width="1.5" />
                    </svg>
                </div>

                <!-- Footer Tagline from Reference Image -->
                <div class="relative z-10 pl-8">
                    <p
                        class="text-[10px] font-extrabold tracking-wider uppercase text-teal-100 font-mono-code leading-relaxed">
                        INTRODUCING FORENSIC INVENTORY MANAGEMENT SYSTEM &bull; FIMS
                    </p>
                </div>
            </div>

            <!-- RIGHT PANEL: Clean Register Form with Original Code & IDs -->
            <div class="md:w-8/12 p-8 sm:p-12 flex flex-col justify-between bg-white">

                <!-- TOP HEADER -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-teal-800 tracking-tight">CREATE OFFICER ACCOUNT
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">Register official service credentials for FIMS
                            authorization</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-slate-50 p-1 border border-slate-200 flex items-center justify-center shrink-0 shadow-2xs">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Crest"
                            class="w-full h-full object-contain">
                    </div>
                </div>

                <!-- FLASH & CONDITIONAL MANDATE BANNER -->
                <div class="py-4 space-y-4">
                    @include('partials.flash')

                    @if (!$hasForensicHead)
                        <div
                            class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-900 flex items-start space-x-3 shadow-2xs">
                            <div
                                class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div
                                    class="font-extrabold font-mono-code uppercase tracking-wider text-amber-900 text-xs">
                                    DIRECTORATE COMMAND MANDATE &bull; REGISTRATION RESTRICTED</div>
                                <div class="text-[11px] font-semibold text-amber-800 mt-0.5 leading-relaxed">
                                    The <strong>Head of Forensic Directorate</strong> must register first as the primary
                                    commanding authority before Unit Heads and Field Operational Staff members can
                                    register accounts.
                                </div>
                            </div>
                        </div>
                    @endif

                    <div id="registerErrorBox" class="hidden p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="registerErrorText"></span>
                    </div>

                    <form id="registerForm" method="POST" action="{{ url('/register') }}"
                        onsubmit="handleRegisterSubmit(event)">
                        @csrf
                        <div class="space-y-4">

                            <!-- ROW 1: FULL NAME & USERNAME -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="ref-input-line pb-1">
                                    <label
                                        class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                        Full Name <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center">
                                        <input type="text" id="fullname" name="fullname" required
                                            value="{{ old('fullname') }}"
                                            oninput="checkPasswordStrength(document.getElementById('password').value)"
                                            placeholder="e.g. Insp. Samuel Mensah"
                                            class="w-full py-1.5 bg-transparent text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none pr-7">
                                        <svg class="w-4 h-4 text-teal-700 absolute right-0 pointer-events-none"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    @error('fullname')
                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="ref-input-line pb-1">
                                    <label
                                        class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                        Username / Email <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center">
                                        <input type="text" id="username" name="username" required
                                            value="{{ old('username') }}"
                                            oninput="checkPasswordStrength(document.getElementById('password').value)"
                                            placeholder="e.g. smensah or officer@nacoc.gov.gh"
                                            class="w-full py-1.5 bg-transparent text-xs text-slate-900 font-semibold placeholder-slate-400 font-mono-code focus:outline-none pr-7">
                                        <svg class="w-4 h-4 text-teal-700 absolute right-0 pointer-events-none"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                    </div>
                                    @error('username')
                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- ROW 2: SERVICE ID NUMBER & PHONE NUMBER -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="ref-input-line pb-1">
                                    <label
                                        class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1 flex items-center justify-between">
                                        <span>Service ID Number <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[10px] text-teal-700 font-extrabold font-mono-code">Format:
                                            SD-345</span>
                                    </label>

                                    <div class="flex items-center space-x-2 relative">
                                        <!-- CUSTOM PREFIX DROPDOWN TRIGGER -->
                                        <div class="relative shrink-0" id="prefixDropdownWrapper">
                                            <button type="button" id="prefixDropdownTrigger"
                                                onclick="togglePrefixDropdown(event)"
                                                class="px-2.5 py-1 bg-teal-50 border border-teal-200 text-xs font-black font-mono-code text-teal-800 flex items-center space-x-1.5 hover:bg-teal-100 rounded-lg transition-colors cursor-pointer select-none">
                                                <span id="selectedPrefixBadge"
                                                    class="px-1.5 py-0.5 rounded bg-teal-700 text-white font-black text-[10px] font-mono-code">{{ old('service_prefix', 'SD') }}</span>
                                                <svg class="w-3 h-3 text-teal-700 transition-transform duration-200"
                                                    id="prefixArrow" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <!-- HIDDEN INPUT FOR FORM SUBMISSION -->
                                            <input type="hidden" id="service_prefix" name="service_prefix"
                                                value="{{ old('service_prefix', 'SD') }}">

                                            <!-- CUSTOM DROPDOWN POPOVER MENU -->
                                            <div id="prefixMenu"
                                                class="hidden absolute top-full left-0 mt-1 w-24 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden p-1 space-y-1">
                                                <div onclick="selectPrefixOption('SD')"
                                                    class="prefix-option px-3 py-1.5 rounded-lg hover:bg-teal-50 cursor-pointer transition-colors flex items-center space-x-2 font-mono-code font-black text-xs text-teal-800">
                                                    <span class="w-2 h-2 rounded-full bg-teal-700"></span>
                                                    <span>SD</span>
                                                </div>

                                                <div onclick="selectPrefixOption('JD')"
                                                    class="prefix-option px-3 py-1.5 rounded-lg hover:bg-teal-50 cursor-pointer transition-colors flex items-center space-x-2 font-mono-code font-black text-xs text-teal-800">
                                                    <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                                    <span>JD</span>
                                                </div>
                                            </div>
                                        </div>

                                        <span
                                            class="font-mono-code font-black text-slate-400 select-none text-xs">-</span>

                                        <!-- NUMERIC ID INPUT -->
                                        <div class="relative flex-1">
                                            <input type="text" id="service_number" name="service_number" required
                                                maxlength="6" inputmode="numeric"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateFullServiceId();"
                                                placeholder="345" value="{{ old('service_number') }}"
                                                class="w-full py-1 bg-transparent text-xs text-slate-900 font-bold placeholder-slate-400 font-mono-code focus:outline-none">
                                        </div>

                                        <!-- HIDDEN FULL SERVICE_ID FIELD FOR FORM SUBMIT -->
                                        <input type="hidden" id="service_id" name="service_id"
                                            value="{{ old('service_id') }}">
                                    </div>
                                    @error('service_id')
                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                    @error('service_number')
                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="ref-input-line pb-1">
                                    <label
                                        class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                        Phone Number <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center">
                                        <input type="tel" id="phone_number" name="phone_number" required
                                            maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                            value="{{ old('phone_number') }}"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                            placeholder="e.g. 0200000000"
                                            class="w-full py-1.5 bg-transparent text-xs text-slate-900 font-semibold placeholder-slate-400 font-mono-code focus:outline-none pr-7">
                                        <svg class="w-4 h-4 text-teal-700 absolute right-0 pointer-events-none"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    @error('phone_number')
                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- TOGGLE SWITCH / ROLE BANNER -->
                            @if (!$hasForensicHead)
                                <div
                                    class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-amber-500 text-white font-bold flex items-center justify-center shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs font-extrabold text-amber-900">Mandatory Role: Head of
                                                Forensic Directorate</div>
                                            <div class="text-[10px] text-amber-700 font-medium">Primary System Command
                                                Registration</div>
                                        </div>
                                    </div>
                                    <span
                                        class="px-2.5 py-1 bg-amber-600 text-white rounded-lg text-[10px] font-extrabold font-mono-code uppercase shadow-xs">DIRECTOR
                                        HEAD</span>
                                </div>
                            @else
                                <div
                                    class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-teal-100 border border-teal-200 flex items-center justify-center text-teal-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900">User is Staff Member</div>
                                            <div class="text-[10px] text-slate-500">Toggle ON for operational clearance
                                                (Toggle OFF for Unit Head)</div>
                                        </div>
                                    </div>

                                    <!-- TOGGLE SWITCH BUTTON -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="isStaffToggle" name="is_staff" {{ old('role', 'staff') === 'head' ? '' : 'checked' }}
                                            onchange="toggleStaffStatus(this)" class="sr-only peer">
                                        <input type="hidden" id="userRoleInput" name="role" value="{{ old('role', 'staff') }}">
                                        <div
                                            class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-700">
                                        </div>
                                    </label>
                                </div>
                            @endif

                            @php
                                $defaultUnit = isset($units) ? $units->first() : null;
                                $defaultUnitName = old('unit', $defaultUnit ? $defaultUnit->name : 'Crime Scene Support');
                                $defaultHeadDisplay =
                                    isset($registeredHeads) && isset($registeredHeads[$defaultUnitName])
                                        ? $registeredHeads[$defaultUnitName]
                                        : 'Your unit head has not registered yet';
                            @endphp

                            <!-- ROW 3: UNITS / DIVISION & ASSIGNED UNIT HEAD -->
                            @if (!$hasForensicHead)
                                <div class="col-span-1 sm:col-span-2 ref-input-line pb-1">
                                    <label
                                        class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">Assigned
                                        Directorate / Division</label>
                                    <input type="text" readonly value="Forensic Directorate"
                                        class="w-full py-1.5 bg-transparent text-xs font-extrabold font-mono-code text-teal-900 cursor-not-allowed select-none">
                                    <input type="hidden" name="unit" value="Forensic Directorate">
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="unitsGrid">
                                    <!-- UNITS CUSTOM SELECT -->
                                    <div id="unitsDropdownWrapper" class="col-span-1">
                                        <label
                                            class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">Units
                                            / Division <span class="text-red-600 font-bold">*</span></label>

                                        <div class="relative" id="select2Container">
                                            <button type="button" id="select2Trigger"
                                                onclick="toggleSelect2Dropdown()"
                                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 flex items-center justify-between focus:outline-none focus:bg-white focus:border-teal-700 transition-all cursor-pointer">
                                                <div class="flex items-center space-x-2 truncate">
                                                    <svg class="w-4 h-4 text-teal-700 shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V7m5 5v6m-5-6v6" />
                                                    </svg>
                                                    <span id="selectedUnitText"
                                                        class="font-semibold text-slate-900">{{ $defaultUnitName }}</span>
                                                </div>
                                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                                    id="select2Arrow" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <input type="hidden" id="selectedUnitInput" name="unit"
                                                value="{{ $defaultUnitName }}">
                                            @error('unit')
                                                <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                            @enderror

                                            <!-- SELECT2 POPUP -->
                                            <div id="select2Menu"
                                                class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-slate-300 rounded-xl shadow-xl z-50 overflow-hidden">
                                                <div class="p-2 border-b border-slate-200 bg-slate-50">
                                                    <input type="text" id="select2SearchInput"
                                                        onkeyup="filterSelect2Options()"
                                                        placeholder="Search unit name..."
                                                        class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-teal-700 font-mono-code">
                                                </div>

                                                <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 py-1"
                                                    id="select2OptionsList">
                                                    @php
                                                        $officialUnits = [
                                                            [
                                                                'name' => 'Crime Scene Support',
                                                                'category' => 'FIELD OPS',
                                                            ],
                                                            ['name' => 'Lab Analysis', 'category' => 'LAB ANALYSIS'],
                                                            [
                                                                'name' => 'Evidence Management',
                                                                'category' => 'CUSTODY & VAULT',
                                                            ],
                                                            [
                                                                'name' => 'Digital & Physical Forensic Unit',
                                                                'category' => 'CYBER & TECH',
                                                            ],
                                                        ];
                                                        $allUnitsList =
                                                            isset($units) && count($units) > 0
                                                                ? $units
                                                                : $officialUnits;
                                                    @endphp
                                                    @foreach ($allUnitsList as $u)
                                                        @php
                                                            $uName = is_object($u) ? $u->name : $u['name'];
                                                            $uCat = is_object($u) ? $u->category : $u['category'];
                                                        @endphp
                                                        <div onclick="selectUnitOption('{{ $uName }}')"
                                                            class="select2-option px-3 py-2 hover:bg-teal-50 text-xs font-semibold text-slate-800 cursor-pointer flex items-center justify-between">
                                                            <span>{{ $uName }}</span>
                                                            <span
                                                                class="text-[10px] text-teal-700 font-mono-code font-bold">{{ $uCat }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ASSIGNED UNIT HEAD -->
                                    <div id="unitHeadContainer" class="col-span-1 transition-all duration-200">
                                        <label
                                            class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">Assigned
                                            Unit Head</label>
                                        <div class="relative">
                                            <input type="text" id="unitHeadInput" name="unit_head" readonly
                                                value="{{ $defaultHeadDisplay }}"
                                                class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs text-slate-800 font-semibold font-mono-code cursor-not-allowed select-none focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- PASSWORD & CONFIRM PASSWORD -->
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="ref-input-line pb-1">
                                        <label
                                            class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                            Password <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div class="relative flex items-center">
                                            <input type="password" id="password" name="password" required
                                                oninput="checkPasswordStrength(this.value); checkPasswordMatch();"
                                                placeholder="Min. 8 characters"
                                                class="w-full py-1.5 bg-transparent text-xs text-slate-900 font-semibold placeholder-slate-400 font-mono-code focus:outline-none pr-7">
                                            <button type="button" onclick="toggleFieldPassword('password')"
                                                class="absolute right-0 text-teal-700 hover:text-teal-900 cursor-pointer focus:outline-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="ref-input-line pb-1">
                                        <label
                                            class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                            Confirm Password <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div class="relative flex items-center">
                                            <input type="password" id="confirm_password" name="password_confirmation"
                                                required oninput="checkPasswordMatch()"
                                                placeholder="Re-enter password"
                                                class="w-full py-1.5 bg-transparent text-xs text-slate-900 font-semibold placeholder-slate-400 font-mono-code focus:outline-none pr-7">
                                            <button type="button" onclick="toggleFieldPassword('confirm_password')"
                                                class="absolute right-0 text-teal-700 hover:text-teal-900 cursor-pointer focus:outline-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div id="matchNotice"
                                            class="hidden text-[10px] font-mono-code mt-1 text-red-600 font-bold">
                                            Passwords do not match</div>
                                        @error('password_confirmation')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                @error('password')
                                    <p class="text-[11px] text-red-600 mt-0.5 font-semibold">{{ $message }}</p>
                                @enderror

                                <!-- PASSWORD STRENGTH PROGRESS BAR -->
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-mono-code">
                                        <span class="text-slate-600">Password Strength:</span>
                                        <span id="strengthText" class="font-bold text-slate-400">Enter Password</span>
                                    </div>

                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div id="strengthBar"
                                            class="h-1.5 w-0 bg-slate-400 transition-all duration-300 rounded-full">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1 pt-1 text-[10px] font-mono-code">
                                        <div id="reqLen"
                                            class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">
                                            8+ Chars</div>
                                        <div id="reqCaps"
                                            class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">
                                            Caps (A-Z)</div>
                                        <div id="reqNum"
                                            class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">
                                            Num (0-9)</div>
                                        <div id="reqSym"
                                            class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">
                                            Symbol</div>
                                        <div id="reqNoUser"
                                            class="col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">
                                            No Name/User</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TERMS CHECKBOX -->
                            <div class="pt-1">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" id="termsCheckbox" name="terms" required
                                        class="w-4 h-4 rounded text-teal-700 focus:ring-teal-700 border-slate-300">
                                    <span class="text-xs text-slate-700 font-medium">I agree to NACOC Security Policy
                                        &amp; Confidentiality Mandate <span
                                            class="text-red-600 font-bold">*</span></span>
                                </label>
                                @error('terms')
                                    <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <div class="pt-2">
                                <button type="submit" id="regSubmitBtn"
                                    class="w-full py-3.5 px-6 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white text-sm font-extrabold rounded-2xl shadow-md shadow-teal-700/30 transition-all hover:scale-[1.01] flex items-center justify-center space-x-2 cursor-pointer">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span
                                        id="regBtnText">{{ !$hasForensicHead ? 'Register as Head of Forensic' : 'Create Service Account' }}</span>
                                </button>
                            </div>

                            <!-- LOGIN LINK -->
                            <div class="text-center pt-2">
                                <p class="text-xs text-slate-600">
                                    Already have an account?
                                    <a href="{{ url('/login') }}"
                                        onclick="navigateWithFade(event, '{{ url('/login') }}')"
                                        class="font-bold text-teal-700 hover:text-teal-900 hover:underline transition-colors">Sign
                                        In</a>
                                </p>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- BOTTOM LINKS -->
                <div
                    class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-teal-700">
                    <span class="text-[11px] text-slate-400 font-mono-code">NACOC FORENSICS &bull; HQ ACCRA</span>
                    <div class="space-x-4">
                        <a href="{{ url('/login') }}" class="hover:underline">Login Portal</a>
                        <a href="#"
                            onclick="alert('For enrollment assistance, contact NACOC Directorate Registrar: ext 4022'); return false;"
                            class="hover:underline">Help</a>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- FOOTER BAR -->
    <footer
        class="w-full px-6 py-3 bg-white/95 border-t border-teal-900/10 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-600 font-mono-code gap-2 relative z-10 backdrop-blur-sm">
        <div>
            Official Property of the Narcotics Control Commission, Ghana &bull; Forensic Inventory Management System
        </div>
        <div class="flex items-center space-x-4">
            <a href="#" class="hover:text-teal-800">Security Policy</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-teal-800">Chain of Custody Spec</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-teal-800">IT Support Desk</a>
        </div>
    </footer>

    <!-- PRESERVED EXACT JAVASCRIPT LOGIC -->
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

            const fullnameVal = document.getElementById('fullname') ? document.getElementById('fullname').value
                .toLowerCase().trim() : '';
            const usernameVal = document.getElementById('username') ? document.getElementById('username').value
                .toLowerCase().trim() : '';
            const lowerPassword = val.toLowerCase();

            const hasLen = val.length >= 8;
            const hasCaps = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSym = /[^A-Za-z0-9]/.test(val);

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

            updateBadge(reqLen, hasLen);
            updateBadge(reqCaps, hasCaps);
            updateBadge(reqNum, hasNum);
            updateBadge(reqSym, hasSym);

            if (reqNoUser) {
                if (val.length > 0 && containsUserOrName) {
                    reqNoUser.className =
                        'col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-bold text-center border border-red-300 transition-colors';
                } else if (val.length > 0 && noUser) {
                    reqNoUser.className =
                        'col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-teal-100 text-teal-800 font-bold text-center border border-teal-300 transition-colors';
                } else {
                    reqNoUser.className =
                        'col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors';
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
                bar.className = 'h-1.5 bg-teal-600 transition-all duration-300 rounded-full';
                text.innerText = 'Good';
                text.className = 'font-bold text-teal-700';
            } else if (score === 4) {
                bar.style.width = '100%';
                bar.className = 'h-1.5 bg-teal-700 transition-all duration-300 rounded-full';
                text.innerText = 'Strong & Secure';
                text.className = 'font-bold text-teal-800';
            }
        }

        function updateBadge(elem, passed) {
            if (!elem) return;
            if (passed) {
                elem.className =
                    'px-1.5 py-0.5 rounded bg-teal-100 text-teal-800 font-bold text-center border border-teal-300 transition-colors';
            } else {
                elem.className =
                    'px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors';
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

        const registeredHeadsMap = @json($registeredHeads ?? []);

        function updateUnitHeadDisplay() {
            const isStaffChecked = document.getElementById('isStaffToggle') ? document.getElementById('isStaffToggle')
                .checked : false;
            const currentUnit = document.getElementById('selectedUnitInput') ? document.getElementById('selectedUnitInput')
                .value : 'Crime Scene Support';
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

        function toggleStaffStatus(checkbox) {
            const roleInput = document.getElementById('userRoleInput');
            if (roleInput) {
                roleInput.value = checkbox.checked ? 'staff' : 'head';
            }
            updateUnitHeadDisplay();
        }

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

        function selectUnitOption(val) {
            document.getElementById('selectedUnitText').innerText = val;
            document.getElementById('selectedUnitInput').value = val;

            document.getElementById('select2Menu').classList.add('hidden');
            document.getElementById('select2Arrow').classList.remove('rotate-180');

            updateUnitHeadDisplay();
        }

        document.addEventListener('DOMContentLoaded', updateUnitHeadDisplay);
        updateUnitHeadDisplay();

        document.addEventListener('click', function(e) {
            const container = document.getElementById('select2Container');
            if (container && !container.contains(e.target)) {
                document.getElementById('select2Menu').classList.add('hidden');
                document.getElementById('select2Arrow').classList.remove('rotate-180');
            }
        });

        function showRegisterError(msg) {
            const errBox = document.getElementById('registerErrorBox');
            const errText = document.getElementById('registerErrorText');
            if (errBox && errText) {
                errText.innerText = msg;
                errBox.classList.remove('hidden');
                errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function handleRegisterSubmit(e) {
            e.preventDefault();

            const errBox = document.getElementById('registerErrorBox');
            if (errBox) errBox.classList.add('hidden');

            const fullnameVal = document.getElementById('fullname') ? document.getElementById('fullname').value.trim() : '';
            if (!fullnameVal) {
                showRegisterError("Validation Error: Please enter your Full Name.");
                return;
            }

            const usernameVal = document.getElementById('username') ? document.getElementById('username').value.trim() : '';
            if (!usernameVal || usernameVal.length < 3) {
                showRegisterError("Validation Error: Username must be at least 3 characters.");
                return;
            }

            const serviceNumVal = document.getElementById('service_number') ? document.getElementById('service_number').value.trim() : '';
            if (!serviceNumVal || serviceNumVal.length < 3) {
                showRegisterError("Validation Error: Service ID Number must have at least 3 digits (e.g. 345).");
                return;
            }

            const phoneVal = document.getElementById('phone_number') ? document.getElementById('phone_number').value.trim() : '';
            if (!/^[0-9]{10}$/.test(phoneVal)) {
                showRegisterError("Validation Error: Phone Number must be exactly 10 digits (e.g. 0200000000).");
                return;
            }

            const p1 = document.getElementById('password') ? document.getElementById('password').value : '';
            const p2 = document.getElementById('confirm_password') ? document.getElementById('confirm_password').value : '';
            const lowerPassword = p1.toLowerCase();
            const lowerUsername = usernameVal.toLowerCase();
            const lowerFullname = fullnameVal.toLowerCase();

            let containsUserOrName = false;
            if (lowerUsername.length >= 3 && lowerPassword.includes(lowerUsername)) {
                containsUserOrName = true;
            }
            if (lowerFullname.length >= 3) {
                const nameParts = lowerFullname.split(/\s+/);
                for (let part of nameParts) {
                    if (part.length >= 3 && lowerPassword.includes(part)) {
                        containsUserOrName = true;
                        break;
                    }
                }
            }

            if (containsUserOrName) {
                showRegisterError("Security Policy: Your password MUST NOT contain your name or username.");
                return;
            }

            if (p1.length < 8 || !/[A-Z]/.test(p1) || !/[0-9]/.test(p1)) {
                showRegisterError("Security Policy: Password must be at least 8 characters long and contain uppercase letters and numbers.");
                return;
            }

            if (p1 !== p2) {
                showRegisterError("Validation Error: Passwords do not match. Please re-enter.");
                return;
            }

            const termsChecked = document.getElementById('termsCheckbox') ? document.getElementById('termsCheckbox').checked : false;
            if (!termsChecked) {
                showRegisterError("Validation Error: You must agree to the NACOC Security Policy & Confidentiality Mandate before registering.");
                return;
            }

            updateFullServiceId();

            const regSubmitBtn = document.getElementById('regSubmitBtn');
            const regBtnText = document.getElementById('regBtnText');

            if (regSubmitBtn && regBtnText) {
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
            }

            document.getElementById('registerForm').submit();
        }

        function togglePrefixDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('prefixMenu');
            const arrow = document.getElementById('prefixArrow');
            if (menu) {
                menu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            }
        }

        function selectPrefixOption(prefix) {
            const hiddenPrefix = document.getElementById('service_prefix');
            const badge = document.getElementById('selectedPrefixBadge');
            const menu = document.getElementById('prefixMenu');
            const arrow = document.getElementById('prefixArrow');

            if (hiddenPrefix) hiddenPrefix.value = prefix;
            if (badge) {
                badge.innerText = prefix;
            }

            if (menu) menu.classList.add('hidden');
            if (arrow) arrow.classList.remove('rotate-180');

            updateFullServiceId();
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('prefixDropdownWrapper');
            const menu = document.getElementById('prefixMenu');
            const arrow = document.getElementById('prefixArrow');
            if (wrapper && menu && !wrapper.contains(e.target)) {
                menu.classList.add('hidden');
                if (arrow) arrow.classList.remove('rotate-180');
            }
        });

        function updateFullServiceId() {
            const prefixEl = document.getElementById('service_prefix');
            const numEl = document.getElementById('service_number');
            const fullIdEl = document.getElementById('service_id');
            if (prefixEl && numEl && fullIdEl) {
                const prefix = prefixEl.value;
                const num = numEl.value.trim();
                fullIdEl.value = num ? `${prefix}-${num}` : '';
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateFullServiceId);
        } else {
            updateFullServiceId();
        }
    </script>
</body>

</html>
