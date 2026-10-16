@php
    $authUser = $user ?? auth()->user();
    $userName = $authUser->fullname ?? 'Officer';
    $userUnit = $userUnit ?? ($authUser->unit ?? 'Forensic Directorate');
    $serviceId = $authUser->service_id ?? 'NACOC-OFFICER';
    $userAvatar = $authUser->avatar ?? null;

    $isForensicHeadSidebar = ($authUser && $authUser->is_head && (
        str_contains(strtolower($authUser->unit ?? ''), 'forensic directorate') ||
        str_contains(strtolower($authUser->unit ?? ''), 'forensic head') ||
        str_contains(strtolower($authUser->unit ?? ''), 'directorate head')
    ));

    $nameParts = preg_split('/\s+/', trim($userName));
    $initials = count($nameParts) >= 2 
        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
        : strtoupper(substr($userName, 0, 2));

    $backDashboardUrl = $backUrl ?? ($isForensicHeadSidebar ? url('/Forensic_Head') : url('/Crime_Scene_Support/head?from=' . urlencode($userUnit)));
    $displayRole = $roleLabel ?? ($authUser->is_head ? 'Head of Unit (' . $userUnit . ')' : 'Forensic Staff Officer');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile Settings &amp; Officer Terminal | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind / Vite -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        /* Tab buttons */
        .tab-btn {
            color: #64748B;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-btn:hover {
            color: #0F172A;
            background: rgba(241, 245, 249, 0.8);
        }
        .tab-btn.active {
            background: #FFFFFF;
            color: #059669;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.08), 0 2px 4px -1px rgba(15, 23, 42, 0.04);
            border-color: #E2E8F0;
        }
    </style>
</head>
<body class="h-full flex flex-col bg-[#F8FAFC] text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- APP CONTAINER: ASIDE BAR & MAIN WORKSPACE -->
    <div class="flex h-screen overflow-hidden">

        <!-- ASIDE BAR -->
        @if($isForensicHeadSidebar)
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F8FAFC] overflow-hidden">

            <!-- SYSTEM TOP NAVBAR -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs z-20">
                <!-- BREADCRUMBS -->
                <div class="flex items-center space-x-2.5 text-xs font-semibold">
                    <a href="{{ $backDashboardUrl }}" class="flex items-center text-slate-500 hover:text-emerald-600 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Command Dashboard</span>
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-500">{{ $userUnit }}</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-900 font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Profile Settings</span>
                    </span>
                </div>

                <!-- TOP ACTIONS -->
                <div class="flex items-center space-x-3">
                    <div class="hidden sm:flex items-center px-3 py-1 bg-slate-100/90 rounded-full border border-slate-200/90 text-[11px] font-mono-code font-bold text-slate-700 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                        <span>{{ $serviceId }}</span>
                    </div>

                    <a href="{{ $backDashboardUrl }}" class="inline-flex items-center px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 text-xs font-semibold rounded-xl border border-slate-200 shadow-2xs transition-all hover:shadow-xs">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Return to Workspace</span>
                    </a>
                </div>
            </header>

            <!-- MAIN SCROLLABLE CONTENT (EXPANDED WIDTH) -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-7 lg:p-8 space-y-6">

                <!-- FLASH NOTIFICATIONS (TOP RIGHT CORNER) -->
                @include('partials.flash')

                <!-- NAVIGATION TABS (FULL WIDTH) -->
                <div class="w-full flex items-center justify-between border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-1.5 bg-slate-200/70 p-1 rounded-2xl border border-slate-200">
                        <button type="button" onclick="switchSettingsTab('profileTab', this)" class="tab-btn active px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer border border-transparent">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Officer Bio-Data</span>
                        </button>
                        <button type="button" onclick="switchSettingsTab('securityTab', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer border border-transparent">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Security &amp; Password</span>
                        </button>
                    </div>

                    <div class="hidden md:flex items-center text-xs text-slate-500 gap-1.5 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>TLS 1.3 Certified Terminal</span>
                    </div>
                </div>

                <!-- MAIN WORKSPACE PANELS -->
                <div class="space-y-6">

                    <!-- TAB 1: BIO-DATA & CONTACT INFORMATION (FULL WIDTH) -->
                    <div id="profileTab" class="settings-tab-panel">
                        <div class="w-full bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 lg:p-10 space-y-6">
                            
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100 shadow-2xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Officer Information &amp; Contact Dossier</h2>
                                        <p class="text-xs text-slate-500">Update official contact records and credentials</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold font-mono-code rounded-lg uppercase">
                                    ACTIVE PROFILE
                                </span>
                            </div>

                            <form id="profileForm" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <!-- FORM FIELDS IN BALANCED 2-COLUMN GRID -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    
                                    <!-- FULL NAME INPUT -->
                                    <div>
                                        <label for="fullname" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Full Official Name <span class="text-red-500">*</span></span>
                                            <span class="text-[10px] text-slate-400 font-normal">Legal Title &amp; Name</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input 
                                                type="text" 
                                                id="fullname" 
                                                name="fullname" 
                                                value="{{ old('fullname', $authUser->fullname) }}" 
                                                required
                                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/15 transition-all @error('fullname') border-red-500 @enderror"
                                                placeholder="e.g. Supt. Eric Mensah"
                                            >
                                        </div>
                                        @error('fullname')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- OFFICIAL EMAIL ADDRESS -->
                                    <div>
                                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Official Email Address</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Official dispatch</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                                            </span>
                                            <input 
                                                type="email" 
                                                id="email" 
                                                name="email" 
                                                value="{{ old('email', $authUser->email) }}" 
                                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/15 transition-all @error('email') border-red-500 @enderror"
                                                placeholder="eric.mensah@nacoc.gov.gh"
                                            >
                                        </div>
                                        @error('email')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- SERVICE ID -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Service / Badge ID</span>
                                            <span class="text-[10px] text-slate-400 font-normal flex items-center gap-1">
                                                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                HQ Assigned
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            </span>
                                            <input 
                                                type="text" 
                                                value="{{ $serviceId }}" 
                                                class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-600 font-mono-code"
                                                title="Official Service ID is designated by Directorate Command."
                                            >
                                        </div>
                                    </div>

                                    <!-- ASSIGNED UNIT -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Assigned Directorate Unit</span>
                                            <span class="text-[10px] text-slate-400 font-normal flex items-center gap-1">
                                                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                Unit Locked
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            </span>
                                            <input 
                                                type="text" 
                                                value="{{ $userUnit }}" 
                                                disabled 
                                                class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-600 cursor-not-allowed select-none"
                                                title="Directorate Unit is designated by Headquarters."
                                            >
                                        </div>
                                    </div>

                                    <!-- USERNAME -->
                                    <div>
                                        <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Username <span class="text-red-500">*</span></span>
                                            <span class="text-[10px] text-slate-400 font-normal">Login handle</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-mono-code font-bold">@</span>
                                            <input 
                                                type="text" 
                                                id="username" 
                                                name="username" 
                                                value="{{ old('username', $authUser->username) }}" 
                                                required
                                                class="w-full pl-9 pr-4 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 font-mono-code focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/15 transition-all @error('username') border-red-500 @enderror"
                                                placeholder="e.g. emensah"
                                            >
                                        </div>
                                        @error('username')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- PHONE NUMBER -->
                                    <div>
                                        <label for="phone_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Contact Phone Number <span class="text-red-500">*</span></span>
                                            <span class="text-[10px] text-slate-400 font-normal">Ghana (10 digits)</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input 
                                                type="tel" 
                                                id="phone_number" 
                                                name="phone_number" 
                                                value="{{ old('phone_number', $authUser->phone_number) }}" 
                                                maxlength="10"
                                                required
                                                pattern="[0-9]{10}"
                                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 font-mono-code focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/15 transition-all @error('phone_number') border-red-500 @enderror"
                                                placeholder="0244123456"
                                            >
                                        </div>
                                        @error('phone_number')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                </div>

                                <!-- OFFICIAL DIGITAL SIGNATURE & SIGN-OFF AUTHORIZATION SECTION (FULL WIDTH) -->
                                <div class="pt-6 border-t border-slate-200/80">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3 mb-5">
                                            <div class="flex items-center space-x-3.5">
                                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100/60 text-indigo-600 flex items-center justify-center font-bold border border-indigo-200/60 shadow-xs shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">
                                                        Official Forensic Digital Signature
                                                    </h3>
                                                    <p class="text-xs text-slate-500 mt-0.5">Affixed to evidence dockets, chain-of-custody handovers, and laboratory examination reports</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                                                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-bold font-mono-code rounded-lg border border-indigo-100 uppercase tracking-wider flex items-center gap-1.5 shadow-2xs">
                                                    <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    SIGN-OFF AUTH
                                                </span>
                                                @if(!empty($authUser->signature))
                                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold font-mono-code rounded-lg border border-emerald-200 uppercase tracking-wider flex items-center gap-1.5 shadow-2xs">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        ACTIVE &amp; REGISTERED
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- SIGNATURE WORKSPACE GRID -->
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                            
                                            <!-- LEFT: REGISTERED SIGNATURE PREVIEW (5 COLS) -->
                                            <div class="lg:col-span-5 bg-slate-50/80 border border-slate-200 rounded-2xl p-4 sm:p-5 flex flex-col justify-between space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                        Official Signature Seal
                                                    </span>

                                                    <!-- CLEAR BUTTON IN HEADER -->
                                                    <button 
                                                        type="button" 
                                                        id="clearSealBtn"
                                                        onclick="clearUploadedSignature()" 
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200/80 rounded-lg shadow-2xs transition-all cursor-pointer {{ empty($authUser->signature) ? 'hidden' : '' }}"
                                                        title="Clear uploaded or registered signature"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        <span>Clear Signature</span>
                                                    </button>
                                                </div>

                                                <!-- SIGNATURE DISPLAY CANVAS / BOX -->
                                                <div id="signaturePreviewContainer" class="relative w-full h-36 sm:h-40 bg-white rounded-xl border-2 border-dashed border-slate-200 flex items-center justify-center p-3 overflow-hidden shadow-inner group">
                                                    <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[radial-gradient(#000_1px,transparent_1px)] [background-size:12px_12px]"></div>
                                                    
                                                    @if(!empty($authUser->signature))
                                                        <img id="signaturePreviewImg" src="{{ $authUser->signature }}" alt="Official Signature" class="max-h-full max-w-full object-contain filter drop-shadow-sm select-none">
                                                        <div id="signatureEmptyState" class="hidden text-center p-4 text-slate-400 space-y-1">
                                                            <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                            <p class="text-xs font-semibold">No signature currently registered</p>
                                                            <p class="text-[10px] text-slate-400">Upload a scan or sign below</p>
                                                        </div>
                                                    @else
                                                        <img id="signaturePreviewImg" src="" alt="Official Signature" class="hidden max-h-full max-w-full object-contain filter drop-shadow-sm select-none">
                                                        <div id="signatureEmptyState" class="text-center p-4 text-slate-400 space-y-1">
                                                            <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                            <p class="text-xs font-semibold">No signature currently registered</p>
                                                            <p class="text-[10px] text-slate-400">Upload a scan or draw your signature</p>
                                                        </div>
                                                    @endif

                                                    <div class="absolute bottom-2 right-2 text-[9px] font-mono-code font-bold px-2 py-0.5 rounded bg-slate-900/5 text-slate-500 uppercase tracking-wider pointer-events-none">
                                                        NACOC FORENSIC
                                                    </div>
                                                </div>

                                                <!-- OFFICER INFO & CLEAR ACTION -->
                                                <div class="flex items-center justify-between text-xs pt-1">
                                                    <span class="text-[11px] text-slate-500 font-mono-code">
                                                        Signatory: <strong class="text-slate-700">{{ $authUser->fullname }}</strong>
                                                    </span>
                                                    <button 
                                                        type="button" 
                                                        id="clearSealBtnBottom"
                                                        onclick="clearUploadedSignature()" 
                                                        class="text-[11px] font-bold text-red-600 hover:text-red-700 hover:underline flex items-center gap-1 cursor-pointer transition-colors {{ empty($authUser->signature) ? 'hidden' : '' }}"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        <span>Clear Signature</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- RIGHT: UPLOAD & DRAW TABS (7 COLS) -->
                                            <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-4">
                                                
                                                <!-- SUB-TABS: UPLOAD FILE vs DRAW CANVAS -->
                                                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                                    <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-xl">
                                                        <button 
                                                            type="button" 
                                                            id="tabBtnUpload" 
                                                            onclick="switchSignatureMethod('upload')" 
                                                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-white text-slate-800 shadow-xs cursor-pointer"
                                                        >
                                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                            <span>Upload Image File</span>
                                                        </button>
                                                        <button 
                                                            type="button" 
                                                            id="tabBtnDraw" 
                                                            onclick="switchSignatureMethod('draw')" 
                                                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-slate-900 cursor-pointer"
                                                        >
                                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                            <span>Draw on Digital Pad</span>
                                                        </button>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 font-mono-code hidden sm:inline-block">PNG, JPG, WEBP, SVG (Max 5MB)</span>
                                                </div>

                                                <!-- HIDDEN INPUT FOR BASE64 DATA -->
                                                <input type="hidden" name="signature_data" id="signatureDataInput" value="">

                                                <!-- METHOD 1: FILE UPLOAD PANE -->
                                                <div id="sigMethodUpload" class="space-y-3">
                                                    <label 
                                                        for="signature_file" 
                                                        class="relative border-2 border-dashed border-slate-300 hover:border-indigo-500 bg-slate-50/50 hover:bg-indigo-50/20 rounded-xl p-6 flex flex-col items-center justify-center text-center cursor-pointer transition-all group"
                                                    >
                                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 group-hover:bg-indigo-100 text-indigo-600 flex items-center justify-center mb-2 transition-colors">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                                            Click to upload signature image or drag &amp; drop
                                                        </span>
                                                        <span class="text-[11px] text-slate-400 mt-1">
                                                            Clear signature on white or transparent background recommended
                                                        </span>
                                                        <span id="selectedFileName" class="mt-2 text-xs font-mono-code font-bold text-indigo-700 bg-indigo-100/60 px-3 py-1 rounded-lg hidden"></span>
                                                        
                                                        <input 
                                                            type="file" 
                                                            id="signature_file" 
                                                            name="signature_file" 
                                                            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" 
                                                            onchange="handleSignatureFileSelect(this)" 
                                                            class="sr-only"
                                                        >
                                                    </label>
                                                    @error('signature_file')
                                                        <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                <!-- METHOD 2: INTERACTIVE DIGITAL DRAW CANVAS PANE -->
                                                <div id="sigMethodDraw" class="hidden space-y-3">
                                                    <div class="relative w-full bg-slate-50 border border-slate-300 rounded-xl overflow-hidden shadow-inner">
                                                        <canvas 
                                                            id="signatureCanvas" 
                                                            class="w-full h-44 bg-white cursor-crosshair touch-none"
                                                        ></canvas>
                                                        <div class="absolute bottom-2 left-3 text-[10px] text-slate-400 font-mono-code select-none pointer-events-none">
                                                            Sign with mouse, stylus pen, or fingertip
                                                        </div>
                                                        <div class="absolute bottom-2 right-3 flex items-center gap-2">
                                                            <button 
                                                                type="button" 
                                                                onclick="clearSignatureCanvas()" 
                                                                class="px-2.5 py-1 text-[11px] font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg shadow-2xs cursor-pointer transition-colors"
                                                            >
                                                                Clear Pad
                                                            </button>
                                                            <button 
                                                                type="button" 
                                                                onclick="applyDrawnSignature()" 
                                                                class="px-3 py-1 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs cursor-pointer transition-colors flex items-center gap-1"
                                                            >
                                                                <span>Adopt Signature</span>
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                                        <span class="flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                            Click <strong>Adopt Signature</strong> after signing to stage it for saving.
                                                        </span>
                                                        <span id="drawStatus" class="font-mono-code text-[10px] text-emerald-600 font-bold hidden">
                                                            ✓ STAGED FOR SUBMISSION
                                                        </span>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                    <!-- SUBMIT BUTTON -->
                                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                                    <div class="text-xs text-slate-400 flex items-center gap-1.5 font-medium">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>Press Ctrl+S to save</span>
                                    </div>

                                    <button 
                                        type="submit" 
                                        id="saveProfileBtn"
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/25 transition-all flex items-center gap-2 cursor-pointer hover:shadow-lg hover:shadow-emerald-600/30 active:scale-[0.99]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                        <span>Save Profile Changes</span>
                                    </button>
                                </div>
                            </form>


                        </div>
                    </div>

                    <!-- TAB 2: SECURITY & PASSWORD CREDENTIALS (FULL WIDTH) -->
                    <div id="securityTab" class="settings-tab-panel hidden">
                        <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-6">

                            <!-- PASSWORD CHANGE CARD (7 COLS) -->
                            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">
                                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold border border-amber-100 shadow-2xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide">Security Credentials &amp; Password</h2>
                                            <p class="text-xs text-slate-500">Update terminal encryption key &amp; login credentials</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold font-mono-code rounded-lg uppercase">
                                        CRYPTOGRAPHIC
                                    </span>
                                </div>

                                <form id="passwordForm" method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <!-- CURRENT PASSWORD -->
                                    <div>
                                        <label for="current_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Current Password <span class="text-red-500">*</span></span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 1121 9z"/></svg>
                                            </span>
                                            <input 
                                                type="password" 
                                                id="current_password" 
                                                name="current_password" 
                                                required
                                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 transition-all @error('current_password') border-red-500 @enderror"
                                                placeholder="Enter your current password"
                                            >
                                            <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                        @error('current_password')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- NEW PASSWORD -->
                                    <div>
                                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>New Secure Password <span class="text-red-500">*</span></span>
                                            <span id="strengthLabel" class="text-[10px] font-mono-code font-bold text-slate-400">Min 8 chars</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </span>
                                            <input 
                                                type="password" 
                                                id="password" 
                                                name="password" 
                                                required
                                                oninput="checkPasswordStrength(this.value)"
                                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 transition-all @error('password') border-red-500 @enderror"
                                                placeholder="Create a strong password"
                                            >
                                            <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                        <!-- STRENGTH PROGRESS BAR -->
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden flex">
                                            <div id="strengthBar" class="h-full bg-slate-300 w-0 transition-all duration-300"></div>
                                        </div>
                                        @error('password')
                                            <p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- CONFIRM NEW PASSWORD -->
                                    <div>
                                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>Confirm New Password <span class="text-red-500">*</span></span>
                                            <span id="matchIndicator" class="text-[10px] font-bold font-mono-code text-slate-400"></span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </span>
                                            <input 
                                                type="password" 
                                                id="password_confirmation" 
                                                name="password_confirmation" 
                                                required
                                                oninput="checkPasswordMatch()"
                                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 transition-all"
                                                placeholder="Re-enter your new password"
                                            >
                                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- SUBMIT BUTTON -->
                                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                                        <button 
                                            type="submit" 
                                            class="w-full sm:w-auto px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md shadow-amber-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer hover:shadow-lg hover:shadow-amber-500/30"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>Update Password Credentials</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- PASSWORD POLICIES CHECKLIST CARD (5 COLS) -->
                            <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-4">
                                <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">NACOC Security Compliance</h3>
                                </div>

                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Passwords protect evidence sign-off tokens and chain-of-custody transfer authorization. Follow the mandated rules below:
                                </p>

                                <ul class="space-y-2.5 text-xs text-slate-600">
                                    <li id="rule-length" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors">
                                        <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span>
                                        <span>At least 8 characters in length</span>
                                    </li>
                                    <li id="rule-username" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors">
                                        <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span>
                                        <span>Must not contain username (<strong class="font-mono-code">{{ $authUser->username }}</strong>)</span>
                                    </li>
                                    <li id="rule-name" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors">
                                        <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span>
                                        <span>Must not contain your legal name</span>
                                    </li>
                                    <li id="rule-mix" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors">
                                        <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span>
                                        <span>Variety of letters, digits &amp; special symbols</span>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>



                </div>

            </main>

        </div>

    </div>

    <form id="removeSignatureForm" action="{{ route('profile.signature.remove') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- CLIENT INTERACTIVITY JAVASCRIPT -->
    <script>
        const authUsername = @json(strtolower($authUser->username ?? ''));
        const authFullname = @json(strtolower($authUser->fullname ?? ''));

        // Tab Switching Function
        function switchSettingsTab(targetId, btn) {
            document.querySelectorAll('.settings-tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active');
            });

            const target = document.getElementById(targetId);
            if (target) {
                target.classList.remove('hidden');
            }
            if (btn) {
                btn.classList.add('active');
            }
        }

        // ==========================================
        // OFFICIAL SIGNATURE PAD & UPLOAD CONTROLS
        // ==========================================
        let sigCanvas = null;
        let sigCtx = null;
        let isDrawing = false;
        let hasDrawnSignature = false;

        function setupSignatureCanvas() {
            sigCanvas = document.getElementById('signatureCanvas');
            if (!sigCanvas) return;
            sigCtx = sigCanvas.getContext('2d');

            // Responsive sizing based on rendered container width
            const rect = sigCanvas.getBoundingClientRect();
            if (rect.width > 0) {
                // If width is changed, preserve aspect or set dimension
                const ratio = window.devicePixelRatio || 1;
                sigCanvas.width = rect.width;
                sigCanvas.height = 176;
            } else {
                sigCanvas.width = 500;
                sigCanvas.height = 176;
            }

            sigCtx.lineWidth = 2.5;
            sigCtx.lineCap = 'round';
            sigCtx.lineJoin = 'round';
            sigCtx.strokeStyle = '#0f172a'; // Deep slate ink

            function getCoordinates(e) {
                const cRect = sigCanvas.getBoundingClientRect();
                if (e.touches && e.touches.length > 0) {
                    return {
                        x: e.touches[0].clientX - cRect.left,
                        y: e.touches[0].clientY - cRect.top
                    };
                }
                return {
                    x: e.clientX - cRect.left,
                    y: e.clientY - cRect.top
                };
            }

            function startDraw(e) {
                isDrawing = true;
                const pos = getCoordinates(e);
                sigCtx.beginPath();
                sigCtx.moveTo(pos.x, pos.y);
            }

            function draw(e) {
                if (!isDrawing) return;
                const pos = getCoordinates(e);
                sigCtx.lineTo(pos.x, pos.y);
                sigCtx.stroke();
                hasDrawnSignature = true;
            }

            function stopDraw() {
                if (!isDrawing) return;
                isDrawing = false;
                sigCtx.closePath();
            }

            // Remove existing listeners if any
            sigCanvas.onmousedown = startDraw;
            sigCanvas.onmousemove = draw;
            sigCanvas.onmouseup = stopDraw;
            sigCanvas.onmouseleave = stopDraw;

            sigCanvas.ontouchstart = function(e) {
                e.preventDefault();
                startDraw(e);
            };
            sigCanvas.ontouchmove = function(e) {
                e.preventDefault();
                draw(e);
            };
            sigCanvas.ontouchend = function(e) {
                e.preventDefault();
                stopDraw();
            };
        }

        function switchSignatureMethod(method) {
            const uploadPane = document.getElementById('sigMethodUpload');
            const drawPane = document.getElementById('sigMethodDraw');
            const btnUpload = document.getElementById('tabBtnUpload');
            const btnDraw = document.getElementById('tabBtnDraw');

            if (method === 'upload') {
                uploadPane.classList.remove('hidden');
                drawPane.classList.add('hidden');
                btnUpload.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-white text-slate-800 shadow-xs cursor-pointer';
                btnDraw.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-slate-900 cursor-pointer';
            } else {
                uploadPane.classList.add('hidden');
                drawPane.classList.remove('hidden');
                btnDraw.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 bg-white text-slate-800 shadow-xs cursor-pointer';
                btnUpload.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-slate-900 cursor-pointer';
                
                // Allow CSS transition to finish before computing canvas bounding rect
                setTimeout(setupSignatureCanvas, 60);
            }
        }

        function clearSignatureCanvas() {
            if (!sigCanvas || !sigCtx) setupSignatureCanvas();
            if (!sigCanvas || !sigCtx) return;
            sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            hasDrawnSignature = false;
            const status = document.getElementById('drawStatus');
            if (status) status.classList.add('hidden');
        }

        function applyDrawnSignature() {
            if (!sigCanvas || !sigCtx) setupSignatureCanvas();
            if (!sigCanvas || !hasDrawnSignature) {
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast('Please draw your official signature on the pad before adopting.', 'warning', 'Signature Required');
                } else {
                    alert('Please draw your official signature on the pad before adopting.');
                }
                return;
            }
            const dataUrl = sigCanvas.toDataURL('image/png');
            const dataInput = document.getElementById('signatureDataInput');
            if (dataInput) {
                dataInput.value = dataUrl;
            }

            if (typeof window.showFlashToast === 'function') {
                window.showFlashToast('Drawn signature adopted. Click "Save Changes" to store.', 'success', 'Signature Staged');
            }

            const preview = document.getElementById('signaturePreviewImg');
            const emptyState = document.getElementById('signatureEmptyState');
            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
            }
            if (emptyState) {
                emptyState.classList.add('hidden');
            }

            const status = document.getElementById('drawStatus');
            if (status) status.classList.remove('hidden');

            const clearBtn = document.getElementById('clearSealBtn');
            const clearBtnBottom = document.getElementById('clearSealBtnBottom');
            if (clearBtn) clearBtn.classList.remove('hidden');
            if (clearBtnBottom) clearBtnBottom.classList.remove('hidden');
        }

        function handleSignatureFileSelect(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            const label = document.getElementById('selectedFileName');
            if (label) {
                label.innerText = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                label.classList.remove('hidden');
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('signaturePreviewImg');
                const emptyState = document.getElementById('signatureEmptyState');
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                if (emptyState) {
                    emptyState.classList.add('hidden');
                }
                const dataInput = document.getElementById('signatureDataInput');
                if (dataInput) {
                    dataInput.value = e.target.result;
                }

                const clearBtn = document.getElementById('clearSealBtn');
                const clearBtnBottom = document.getElementById('clearSealBtnBottom');
                if (clearBtn) clearBtn.classList.remove('hidden');
                if (clearBtnBottom) clearBtnBottom.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        // Clear uploaded or registered signature
        const initialSavedSignature = @json($authUser->signature ?? null);

        function clearUploadedSignature() {
            const fileInput = document.getElementById('signature_file');
            const dataInput = document.getElementById('signatureDataInput');
            const preview = document.getElementById('signaturePreviewImg');
            const emptyState = document.getElementById('signatureEmptyState');
            const fileNameBadge = document.getElementById('selectedFileName');
            const clearBtn = document.getElementById('clearSealBtn');
            const clearBtnBottom = document.getElementById('clearSealBtnBottom');
            const drawStatus = document.getElementById('drawStatus');

            const hasNewlyStaged = (fileInput && fileInput.files && fileInput.files.length > 0) || 
                                   (dataInput && dataInput.value && dataInput.value !== (initialSavedSignature || ''));

            if (hasNewlyStaged) {
                // Clear local staged file & drawn data
                if (fileInput) fileInput.value = '';
                if (dataInput) dataInput.value = initialSavedSignature || '';
                if (fileNameBadge) {
                    fileNameBadge.innerText = '';
                    fileNameBadge.classList.add('hidden');
                }
                if (drawStatus) drawStatus.classList.add('hidden');
                if (typeof clearSignatureCanvas === 'function') clearSignatureCanvas();

                // Revert to initial state
                if (initialSavedSignature) {
                    if (preview) {
                        preview.src = initialSavedSignature;
                        preview.classList.remove('hidden');
                    }
                    if (emptyState) emptyState.classList.add('hidden');
                } else {
                    if (preview) {
                        preview.src = '';
                        preview.classList.add('hidden');
                    }
                    if (emptyState) emptyState.classList.remove('hidden');
                    if (clearBtn) clearBtn.classList.add('hidden');
                    if (clearBtnBottom) clearBtnBottom.classList.add('hidden');
                }
                return;
            }

            // If an active signature is already saved in the database, confirm and submit removal
            if (initialSavedSignature) {
                if (confirm('Are you sure you want to clear your registered signature from your profile?')) {
                    const removeForm = document.getElementById('removeSignatureForm');
                    if (removeForm) {
                        removeForm.submit();
                    }
                }
            }
        }

        // Toggle Password Show / Hide
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const eyeIcon = btn.querySelector('.eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                if (eyeIcon) {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>';
                }
            } else {
                input.type = 'password';
                if (eyeIcon) {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
                }
            }
        }

        // Real-time Password Strength Check
        function checkPasswordStrength(val) {
            const bar = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            const ruleLength = document.getElementById('rule-length');
            const ruleUsername = document.getElementById('rule-username');
            const ruleName = document.getElementById('rule-name');
            const ruleMix = document.getElementById('rule-mix');

            let score = 0;
            const lowerVal = val.toLowerCase();

            // 1. Length check
            if (val.length >= 8) {
                score += 25;
                ruleLength.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold transition-colors';
                ruleLength.innerHTML = '<span class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold">&#10003;</span><span>At least 8 characters in length</span>';
            } else {
                ruleLength.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors';
                ruleLength.innerHTML = '<span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span><span>At least 8 characters in length</span>';
            }

            // 2. Username check
            if (authUsername && authUsername.length >= 3 && lowerVal.includes(authUsername)) {
                ruleUsername.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-red-50 border border-red-200 text-red-800 font-semibold transition-colors';
                ruleUsername.innerHTML = '<span class="w-4 h-4 rounded-full bg-red-600 text-white flex items-center justify-center text-[10px] font-bold">&#10007;</span><span>VIOLATION: Contains your username!</span>';
            } else {
                ruleUsername.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold transition-colors';
                ruleUsername.innerHTML = '<span class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold">&#10003;</span><span>Must not contain username</span>';
                score += 25;
            }

            // 3. Name check
            let nameConflict = false;
            if (authFullname) {
                const parts = authFullname.split(/\s+/);
                for (const p of parts) {
                    if (p.length >= 3 && lowerVal.includes(p)) {
                        nameConflict = true;
                        break;
                    }
                }
            }
            if (nameConflict) {
                ruleName.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-red-50 border border-red-200 text-red-800 font-semibold transition-colors';
                ruleName.innerHTML = '<span class="w-4 h-4 rounded-full bg-red-600 text-white flex items-center justify-center text-[10px] font-bold">&#10007;</span><span>VIOLATION: Contains part of your name!</span>';
            } else {
                ruleName.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold transition-colors';
                ruleName.innerHTML = '<span class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold">&#10003;</span><span>Must not contain your legal name</span>';
                score += 25;
            }

            // 4. Mix of chars check
            const hasNum = /[0-9]/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);
            const hasUpper = /[A-Z]/.test(val);
            if ((hasNum && hasUpper) || (hasNum && hasSpecial)) {
                score += 25;
                ruleMix.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold transition-colors';
                ruleMix.innerHTML = '<span class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold">&#10003;</span><span>Variety of letters, digits &amp; symbols</span>';
            } else {
                ruleMix.className = 'flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 transition-colors';
                ruleMix.innerHTML = '<span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] font-bold">&bull;</span><span>Variety of letters, digits &amp; symbols</span>';
            }

            // Render bar & label
            if (val.length === 0) {
                bar.style.width = '0%';
                bar.className = 'h-full bg-slate-300 transition-all duration-300';
                label.innerText = 'Min 8 chars';
                label.className = 'text-[10px] font-mono-code font-bold text-slate-400';
            } else if (score <= 50) {
                bar.style.width = '33%';
                bar.className = 'h-full bg-red-500 transition-all duration-300';
                label.innerText = 'Weak';
                label.className = 'text-[10px] font-mono-code font-bold text-red-500';
            } else if (score < 100) {
                bar.style.width = '66%';
                bar.className = 'h-full bg-amber-500 transition-all duration-300';
                label.innerText = 'Moderate';
                label.className = 'text-[10px] font-mono-code font-bold text-amber-600';
            } else {
                bar.style.width = '100%';
                bar.className = 'h-full bg-emerald-500 transition-all duration-300';
                label.innerText = 'Strong & Compliant';
                label.className = 'text-[10px] font-mono-code font-bold text-emerald-600';
            }

            checkPasswordMatch();
        }

        // Real-time Match Indicator
        function checkPasswordMatch() {
            const pwd = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            const indicator = document.getElementById('matchIndicator');

            if (!confirm) {
                indicator.innerText = '';
                return;
            }

            if (pwd === confirm) {
                indicator.innerText = '✓ Passwords Match';
                indicator.className = 'text-[10px] font-bold font-mono-code text-emerald-600';
            } else {
                indicator.innerText = '✗ Do Not Match';
                indicator.className = 'text-[10px] font-bold font-mono-code text-red-500';
            }
        }

        // Quick copy to clipboard helper
        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerText;
                btn.innerText = 'Copied!';
                btn.classList.add('text-emerald-600');
                setTimeout(() => {
                    btn.innerText = originalText;
                    btn.classList.remove('text-emerald-600');
                }, 2000);
            });
        }

        // Keyboard Shortcut: Ctrl + S / Cmd + S to save profile
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const form = document.getElementById('profileForm');
                if (form) {
                    form.submit();
                }
            }
        });
    </script>

</body>
</html>
