<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Drug Screening Entry | NACOC Forensic Laboratory</title>

    <!-- Tailwind CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,600,700" rel="stylesheet" />

    <!-- jQuery & Select2 -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Select2 Custom Styles */
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single,
        .select2-container--default .select2-selection--single {
            background-color: #FFFFFF !important;
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 1rem !important;
            min-height: 50px !important;
            height: 50px !important;
            max-height: 50px !important;
            display: flex !important;
            align-items: center !important;
            padding-left: 1rem !important;
            padding-right: 64px !important; /* Ample space for clear X and dropdown arrow */
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            position: relative !important;
            overflow: visible !important;
        }
        .select2-container--default .select2-selection--single:hover {
            border-color: #0D9488 !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #0D9488 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2) !important;
        }
        .select2-container .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #0F172A !important;
            line-height: normal !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            font-weight: 600 !important;
            font-size: 0.8125rem !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
            width: auto !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
            position: static !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94A3B8 !important;
            font-weight: 500 !important;
            font-size: 0.8125rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px !important;
            right: 12px !important;
            width: 20px !important;
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            pointer-events: none !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #64748B transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
            transition: transform 0.2s ease !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #0D9488 transparent !important;
            border-width: 0 4px 5px 4px !important;
        }

        /* Perfectly Centered Clear X Button inline with Name & Arrow */
        .select2-container .select2-selection--single .select2-selection__clear,
        .select2-container--default .select2-selection--single .select2-selection__clear,
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection__clear,
        .select2-selection__clear,
        button.select2-selection__clear {
            position: absolute !important;
            right: 34px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #64748B !important;
            font-weight: 800 !important;
            font-size: 0.875rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            cursor: pointer !important;
            z-index: 20 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 22px !important;
            width: 22px !important;
            border-radius: 9999px !important;
            background-color: #F1F5F9 !important;
            border: 1px solid #CBD5E1 !important;
            outline: none !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            transition: all 0.15s ease-in-out !important;
            float: none !important;
            box-sizing: border-box !important;
        }
        .select2-container .select2-selection--single .select2-selection__clear:hover,
        .select2-container--default .select2-selection--single .select2-selection__clear:hover,
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection__clear:hover,
        button.select2-selection__clear:hover {
            background-color: #FEE2E2 !important;
            border-color: #FECACA !important;
            color: #E11D48 !important;
            transform: translateY(-50%) scale(1.08) !important;
        }
        .select2-container .select2-selection--single .select2-selection__clear span,
        .select2-container--default .select2-selection--single .select2-selection__clear span,
        .select2-container--default .select2-selection--single .select2-selection__rendered .select2-selection__clear span,
        button.select2-selection__clear span {
            display: block !important;
            line-height: 1 !important;
            margin-top: -1px !important;
        }

        /* Compact styling for Medication row Select2 fields */
        .select2-medication-field + .select2-container .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            max-height: 38px !important;
            border-radius: 0.75rem !important;
            border: 1.5px solid #CBD5E1 !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding-left: 0.75rem !important;
            padding-right: 2.25rem !important;
        }
        .select2-medication-field + .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .select2-medication-field + .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
            width: 18px !important;
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
        .select2-medication-field + .select2-container .select2-selection--single .select2-selection__clear {
            right: 24px !important;
            height: 18px !important;
            width: 18px !important;
            font-size: 0.75rem !important;
        }
        .select2-dropdown {
            border: 1px solid #E2E8F0 !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            font-size: 0.75rem !important;
            z-index: 100 !important;
            padding: 8px !important;
            background: #FFFFFF !important;
        }
        .select2-search--dropdown {
            padding: 4px 4px 8px 4px !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 0.875rem !important;
            padding: 9px 12px 9px 36px !important;
            font-size: 0.8125rem !important;
            font-family: inherit !important;
            background-color: #F8FAFC !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: 10px center !important;
            background-size: 16px 16px !important;
            outline: none !important;
            transition: all 0.15s ease !important;
        }
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #0D9488 !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.15) !important;
        }
        .select2-results__options {
            max-height: 280px !important;
            padding: 2px !important;
        }
        .select2-container--default .select2-results__option {
            padding: 4px 4px !important;
            border-radius: 0.75rem !important;
            margin-bottom: 4px !important;
            transition: all 0.15s ease !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #F8FAFC !important;
            color: inherit !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #F0FDFA !important;
        }
        .select2-results__option--highlighted .civilian-tag-option {
            border-color: #0D9488 !important;
            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.2) !important;
            transform: translateY(-1px) !important;
        }
        .select2-results__option--highlighted .suspect-item-option {
            background-color: #F1F5F9 !important;
        }
    </style>
</head>

<body class="min-h-screen flex antialiased bg-slate-50 text-slate-800 selection:bg-emerald-600 selection:text-white">
@php
    $isStaffUser = auth()->user() instanceof \App\Models\StaffAccount || request()->is('*/staff*') || (auth()->user() && !auth()->user()->is_head);
    $screeningBaseUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
    $screeningCreateUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening/create') : url('/Lab_Analysis/drug-screening/create');
    $screeningStoreUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
@endphp

    <div class="flex h-screen w-full overflow-hidden">
        <!-- SHARED SIDEBAR -->
        @if($isStaffUser)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Lab Analysis'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3">
                    <a href="{{ $screeningBaseUrl }}"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors flex items-center space-x-1.5 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Back to Screening Register</span>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono-code text-slate-500">Forensic Laboratory Protocol #TOX-2026</span>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">

                {{-- TOP RIGHT FLASH NOTIFICATION (WHITE BACKGROUND) --}}
                @if(session('success') || session('status'))
                <div id="topRightFlashToast" 
                     class="fixed top-5 right-5 z-[999999] max-w-md w-[calc(100vw-2.5rem)] bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden transition-all duration-300 transform translate-x-0 opacity-100 font-sans"
                     style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999999; animation: flashSlideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
                    <div class="p-4 flex items-start gap-3.5 bg-white">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pr-1">
                            <div class="flex items-center gap-1.5 font-mono-code font-bold text-[10.5px] uppercase tracking-wider text-emerald-600">
                                <span>Drug Screening System</span>
                            </div>
                            <div class="text-xs text-slate-800 font-sans mt-1 leading-snug font-semibold">
                                {{ session('success') ?? session('status') }}
                            </div>
                        </div>
                        <button type="button" 
                                onclick="dismissTopRightFlashToast()" 
                                class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors p-1.5 rounded-lg shrink-0 cursor-pointer"
                                title="Dismiss notification">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="w-full bg-slate-100 h-1 overflow-hidden">
                        <div id="flashProgressBar" class="h-full bg-emerald-500" style="width: 100%; transition: width 6s linear;"></div>
                    </div>
                </div>

                <style>
                    @keyframes flashSlideInRight {
                        0% { transform: translateX(110%); opacity: 0; }
                        100% { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes flashSlideOutRight {
                        0% { transform: translateX(0); opacity: 1; }
                        100% { transform: translateX(110%); opacity: 0; }
                    }
                </style>

                <script>
                    function dismissTopRightFlashToast() {
                        const toast = document.getElementById('topRightFlashToast');
                        if (toast) {
                            toast.style.animation = 'flashSlideOutRight 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                            setTimeout(() => toast.remove(), 310);
                        }
                    }
                    document.addEventListener('DOMContentLoaded', () => {
                        const bar = document.getElementById('flashProgressBar');
                        if (bar) {
                            setTimeout(() => { bar.style.width = '0%'; }, 50);
                        }
                        setTimeout(dismissTopRightFlashToast, 6000);
                    });
                </script>
                @endif

                @if($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl shadow-xs">
                    <div class="flex items-center space-x-2 text-rose-800 text-xs font-extrabold mb-1">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Please correct the errors below before submitting:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5 ml-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- HERO BANNER -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-md shadow-slate-200/60 relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 bg-teal-500 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">LAB INTAKE</span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs font-bold text-teal-600 font-mono-code">NEW DRUG SCREENING</span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 flex items-center gap-3">
                                <span>Record Suspect Drug Screening</span>
                            </h1>
                            <p class="text-xs text-slate-600 mt-1 max-w-2xl leading-relaxed">
                                Submit a new biological or chemical drug screening record for an eligible suspect. Eligible candidates include suspects linked to drug commodities or those with no exhibits lodged yet.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FORM CARD -->
                <div class="w-full bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
                    <div class="px-8 py-6 bg-slate-50/70 border-b border-slate-200/80 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 uppercase font-mono-code tracking-wider">
                                Suspect Screening Intake Form
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">All mandatory clinical and suspect identifiers must be populated.</p>
                        </div>
                        <div class="px-3 py-1 bg-teal-50 border border-teal-200 text-teal-800 rounded-xl text-[11px] font-mono-code font-bold">
                            {{ ($suspects ?? $eligibleSuspects)->count() }} Suspects Available
                        </div>
                    </div>

                    <form id="screeningForm" action="{{ $screeningStoreUrl }}" method="POST" class="p-8 space-y-6">
                        @csrf
                        <input type="hidden" name="screening_id" id="screeningIdInput" value="{{ old('screening_id') }}">

                        <!-- UPDATE MODE ALERT BANNER -->
                        <div id="updateModeBanner" class="{{ old('screening_id') ? '' : 'hidden' }} p-4 bg-gradient-to-r from-amber-50 to-amber-100/70 border border-amber-300 rounded-2xl flex items-center justify-between shadow-2xs">
                            <div class="flex items-center space-x-3.5 text-xs">
                                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-amber-950 font-mono-code uppercase text-[11px] flex items-center gap-2">
                                        <span>Update Mode Active</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-200 text-amber-900 font-extrabold">
                                            Dossier #<span id="updateBannerScreeningId">{{ old('screening_id') ? str_pad(old('screening_id'), 3, '0', STR_PAD_LEFT) : '001' }}</span>
                                        </span>
                                    </div>
                                    <div class="text-amber-900 text-xs mt-0.5 font-medium">
                                        Updating existing screening record for <strong id="updateBannerSuspectName" class="font-extrabold">Suspect</strong>. Submitting will update the database.
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="cancelUpdateMode()"
                                class="px-3 py-1.5 bg-white hover:bg-amber-200/60 border border-amber-300 text-amber-900 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                                title="Exit update mode and reset form">
                                Reset to New Entry
                            </button>
                        </div>

                        <!-- 1. PERSON / SUSPECT SELECTION (SELECT2 WITH TAGGING) -->
                        <div class="space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                <label for="suspectSelect" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    1. Suspect Name &amp; Profile <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <span class="text-teal-700 font-mono-code font-bold bg-teal-50 px-2.5 py-0.5 rounded-lg border border-teal-200 shadow-2xs flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Type name &amp; press <strong>Enter ↵</strong> to add Civilian</span>
                                    </span>
                                </div>
                            </div>

                            <input type="hidden" name="civilian_name" id="civilianNameInput" value="{{ old('civilian_name') }}">

                            <div class="relative">
                                <select name="suspect_id" id="suspectSelect"
                                    class="w-full pl-4 pr-10 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all cursor-pointer">
                                    <option value="" {{ (old('suspect_id') || old('civilian_name')) ? '' : 'selected' }}>
                                        -- Search Suspect Name or Type New Civilian Name --
                                    </option>
                                    @if(old('civilian_name'))
                                        <option value="civilian:{{ old('civilian_name') }}" selected data-civilian="true" data-name="{{ old('civilian_name') }}">
                                            {{ old('civilian_name') }} (Civilian)
                                        </option>
                                    @endif
                                    @foreach(($suspects ?? $eligibleSuspects) as $suspect)
                                    @php
                                        $caseNo = $suspect->case_number ?? ($suspect->singleCase->case_number ?? ($suspect->multipleCase->case_number ?? 'N/A'));
                                        $mugshotUrl = $suspect->mugshot_front_url 
                                            ?? \App\Services\CasePhotoResolver::resolveMugshotUrl(
                                                $suspect->mugshot_front 
                                                ?: ($suspect->singleCase?->mugshot_front ?: null)
                                            );
                                    @endphp
                                    <option value="{{ $suspect->id }}"
                                        {{ old('suspect_id') == $suspect->id ? 'selected' : '' }}
                                        data-name="{{ $suspect->suspect_name }}"
                                        data-alias="{{ $suspect->suspect_alias ?? 'N/A' }}"
                                        data-card="{{ $suspect->ghana_card ?? 'N/A' }}"
                                        data-gender="{{ $suspect->gender ?? 'Male' }}"
                                        data-facility="{{ $suspect->holding_facility ?? 'NACOC Holding Center' }}"
                                        data-case="{{ $caseNo }}"
                                        data-mugshot="{{ $mugshotUrl ?? '' }}"
                                        data-reason="Registered Suspect">
                                        {{ $suspect->suspect_name }} (Suspect)
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- DYNAMIC SUSPECT / PERSON PREVIEW CARD -->
                            <div id="suspectPreviewCard" class="hidden mt-3 p-4 bg-slate-50 rounded-2xl border border-slate-200 transition-all">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center space-x-3.5">
                                        <div id="previewAvatarContainer" class="w-12 h-12 rounded-xl bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center border border-teal-200 shrink-0 font-mono-code text-sm">
                                            SP
                                        </div>
                                        <div>
                                            <div id="previewName" class="font-extrabold text-sm text-slate-900">Suspect Name</div>
                                            <div id="previewDetails" class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span>Ghana Card: <strong id="previewCardNo" class="font-mono-code text-slate-700">N/A</strong></span>
                                                <span>&bull;</span>
                                                <span>Facility: <strong id="previewFacility" class="text-slate-700">N/A</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div id="previewCaseNo" class="text-xs font-mono-code font-extrabold text-slate-900">NCC-{{ date('Y') }}</div>
                                        <span id="previewReasonBadge" class="mt-1 inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            Eligible Profile
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. ON MEDICATION & CLINICAL PRESCRIPTIONS -->
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <label class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                        2. On Medication / Clinical Prescriptions <span class="text-rose-500">*</span>
                                    </label>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Is the person currently taking any prescribed, over-the-counter, or regular therapeutic medications?
                                    </p>
                                </div>

                                <!-- Toggle: No / Yes -->
                                <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl border border-slate-200 shrink-0">
                                    <label id="medicationOptionNoLabel"
                                        class="flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all {{ old('on_medication') ? 'text-slate-600 hover:text-slate-900' : 'bg-white text-slate-900 shadow-2xs' }}">
                                        <input type="radio" name="on_medication" value="0" class="sr-only" {{ old('on_medication') ? '' : 'checked' }} onchange="toggleMedicationSection(false)">
                                        <span>No (Not on Meds)</span>
                                    </label>
                                    <label id="medicationOptionYesLabel"
                                        class="flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all {{ old('on_medication') ? 'bg-teal-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                        <input type="radio" name="on_medication" value="1" class="sr-only" {{ old('on_medication') ? 'checked' : '' }} onchange="toggleMedicationSection(true)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>Yes (On Medication)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- DYNAMIC MEDICATION INFORMATION SECTION -->
                            <div id="medicationInfoSection" class="{{ old('on_medication') ? '' : 'hidden' }} space-y-4 p-5 bg-gradient-to-br from-teal-50/50 via-white to-slate-50/80 rounded-3xl border-2 border-teal-500/40 shadow-xs transition-all">
                                <div class="flex items-start sm:items-center justify-between pb-3 border-b border-teal-100/90 gap-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xs font-black text-slate-900 uppercase font-mono-code tracking-wider">
                                                Medication Profile &amp; Prescriptions
                                            </h3>
                                            <p class="text-[11px] text-teal-800 font-medium mt-0.5">
                                                Record active medications, dosages, and administration intervals to cross-reference with toxicology findings.
                                            </p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-mono-code font-bold bg-teal-100 text-teal-800 border border-teal-200 shrink-0">
                                        Multiple Meds Supported
                                    </span>
                                </div>

                                <!-- CONTAINER FOR DYNAMIC MEDICATION CARDS -->
                                <div id="medicationsContainer" class="space-y-4">
                                    <!-- Dynamic rows inserted by JavaScript -->
                                </div>

                                <!-- ADD ANOTHER MEDICATION BUTTON -->
                                <div class="pt-2 flex justify-start">
                                    <button type="button" onclick="addMedicationRow()"
                                        class="px-4 py-2.5 bg-white hover:bg-teal-50 text-teal-700 border-2 border-dashed border-teal-300 hover:border-teal-500 rounded-xl text-xs font-extrabold font-mono-code flex items-center space-x-2 transition-all cursor-pointer shadow-2xs">
                                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>+ Add Another Medication</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. PURPOSE OF SCREENING & 4. DATE OF SCREENING (2 COLUMNS) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-200">
                            <!-- PURPOSE OF SCREENING -->
                            <div class="space-y-2">
                                <label for="purposeInput" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    3. Purpose of Screening <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="purpose" id="purposeInput" required
                                    value=""
                                    placeholder="e.g. Pre-Trial Assessment, Rehabilitation Intake, Arrest Substance Check..."
                                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all">

                                <!-- Quick Preset Chips -->
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    <span class="text-[10px] text-slate-400 font-mono-code font-bold mr-1 self-center">Presets:</span>
                                    <button type="button" onclick="setPurpose('Pre-Trial Substance Toxicology Assessment')"
                                        class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-semibold transition-colors cursor-pointer">
                                        Pre-Trial Assessment
                                    </button>
                                    <button type="button" onclick="setPurpose('Arrest Custody Drug Intake Screening')"
                                        class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-semibold transition-colors cursor-pointer">
                                        Arrest Intake
                                    </button>
                                    <button type="button" onclick="setPurpose('Court-Ordered Toxicology Examination')"
                                        class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-semibold transition-colors cursor-pointer">
                                        Court Order
                                    </button>
                                    <button type="button" onclick="setPurpose('Rehabilitation & Detox Intake Evaluation')"
                                        class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-semibold transition-colors cursor-pointer">
                                        Rehab Intake
                                    </button>
                                </div>
                            </div>

                            <!-- DATE OF SCREENING -->
                            <div class="space-y-2">
                                <label for="screeningDateInput" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    4. Date of Screening <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="screening_date" id="screeningDateInput" required
                                    value="{{ old('screening_date', date('Y-m-d')) }}"
                                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-mono-code font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all cursor-pointer">
                                <p class="text-[11px] text-slate-400">Date the biological/urine sample was obtained or assayed.</p>
                            </div>
                        </div>

                        <!-- 5. SCREENING OUTCOME / RESULTS -->
                        <div class="space-y-3 pt-4 border-t border-slate-200">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                5. Screening Outcome / Results <span class="text-rose-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- POSITIVE -->
                                <label class="relative flex items-center p-4 rounded-2xl border-2 border-slate-200 hover:border-rose-400 cursor-pointer transition-all bg-white result-card"
                                    id="cardPositive">
                                    <input type="radio" name="result" value="Positive" class="sr-only" required
                                        {{ old('result') === 'Positive' ? 'checked' : '' }}
                                        onchange="highlightResultCard('Positive')">
                                    <div class="flex items-center space-x-3 w-full">
                                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-rose-900 font-mono-code">POSITIVE</div>
                                            <div class="text-[10px] text-rose-600 font-medium">Substances detected</div>
                                        </div>
                                    </div>
                                </label>

                                <!-- NEGATIVE -->
                                <label class="relative flex items-center p-4 rounded-2xl border-2 border-slate-200 hover:border-emerald-400 cursor-pointer transition-all bg-white result-card"
                                    id="cardNegative">
                                    <input type="radio" name="result" value="Negative" class="sr-only" required
                                        {{ old('result') === 'Negative' ? 'checked' : '' }}
                                        onchange="highlightResultCard('Negative')">
                                    <div class="flex items-center space-x-3 w-full">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-emerald-900 font-mono-code">NEGATIVE</div>
                                            <div class="text-[10px] text-emerald-600 font-medium">No drug(s) detected</div>
                                        </div>
                                    </div>
                                </label>

                                <!-- PENDING -->
                                <label class="relative flex items-center p-4 rounded-2xl border-2 border-slate-200 hover:border-amber-400 cursor-pointer transition-all bg-white result-card"
                                    id="cardPending">
                                    <input type="radio" name="result" value="Pending" class="sr-only" required
                                        {{ old('result', 'Pending') === 'Pending' ? 'checked' : '' }}
                                        onchange="highlightResultCard('Pending')">
                                    <div class="flex items-center space-x-3 w-full">
                                        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-amber-900 font-mono-code">PENDING</div>
                                            <div class="text-[10px] text-amber-600 font-medium">Awaiting lab results</div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- DYNAMIC SUBSTANCE OUTCOME SECTION -->
                            <div id="substanceOutcomeSection" class="pt-2">
                                <!-- CASE 1: POSITIVE (INPUT BOX ALLOWING USER TO ENTER SUBSTANCE NAME(S)) -->
                                <div id="positiveSubstanceBox" class="hidden p-4 rounded-2xl bg-rose-50/70 border border-rose-200 space-y-3 transition-all">
                                    <div class="flex items-center justify-between">
                                        <label for="substancesFoundInput" class="block text-xs font-extrabold text-rose-900 uppercase font-mono-code tracking-wider">
                                            Name of Substance(s) Found After Screening <span class="text-rose-600">*</span>
                                        </label>
                                        <span class="text-[10px] font-mono-code text-rose-600 font-bold bg-rose-100 px-2 py-0.5 rounded-md border border-rose-200">
                                            Positive Chemical Identification
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <input type="text" name="substances_found" id="substancesFoundInput"
                                            value="{{ old('substances_found') }}"
                                            placeholder="Enter substance name(s) e.g. Cocaine, Heroin, Cannabis, Methamphetamine, Tramadol..."
                                            class="w-full px-4 py-3 bg-white border border-rose-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-rose-600 focus:ring-2 focus:ring-rose-600/20 transition-all">
                                    </div>
                                    <!-- Quick Preset Add Buttons -->
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <span class="text-[10px] text-rose-700 font-mono-code font-bold mr-1">Quick Select:</span>
                                        <button type="button" onclick="addSubstanceTag('Cocaine')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + Cocaine
                                        </button>
                                        <button type="button" onclick="addSubstanceTag('Heroin')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + Heroin
                                        </button>
                                        <button type="button" onclick="addSubstanceTag('Cannabis (THC)')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + Cannabis
                                        </button>
                                        <button type="button" onclick="addSubstanceTag('Methamphetamine')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + Methamphetamine
                                        </button>
                                        <button type="button" onclick="addSubstanceTag('Tramadol')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + Tramadol
                                        </button>
                                        <button type="button" onclick="addSubstanceTag('MDMA / Ecstasy')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-lg text-[10px] font-bold font-mono-code transition-colors cursor-pointer">
                                            + MDMA
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-rose-600 font-medium">
                                        Type the specific drug(s) or substance(s) identified during biological screening.
                                    </p>
                                </div>

                                <!-- CASE 2: NEGATIVE (SHOWS LABEL: No substance(s) found) -->
                                <div id="negativeSubstanceLabel" class="hidden p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 flex items-center justify-between gap-4 transition-all">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-extrabold font-mono-code bg-emerald-600 text-white shadow-2xs">
                                                    No drug(s) detected
                                                </span>
                                                <span class="text-xs font-extrabold text-emerald-950 font-mono-code">
                                                    (Negative Screening Outcome)
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-emerald-700 mt-1 font-medium">
                                                Biological specimen tested clear. No narcotic, psychotropic, or precursor substances were detected.
                                            </div>
                                        </div>
                                    </div>
                                    <span class="hidden sm:inline-flex text-[10px] font-mono-code font-bold uppercase text-emerald-700 bg-emerald-100/70 px-2.5 py-1 rounded-lg border border-emerald-200 shrink-0">
                                        Assay Clear
                                    </span>
                                </div>

                                <!-- CASE 3: PENDING (INFO BANNER) -->
                                <div id="pendingSubstanceBanner" class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200 flex items-center space-x-3 text-xs text-amber-800 transition-all">
                                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="text-[11px]">
                                        <strong class="font-bold">Awaiting Screening Outcome:</strong> Select <strong>Positive</strong> to input detected substances, or <strong>Negative</strong> if no substances were found.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6. NOTES / REMARKS -->
                        <div class="space-y-2 pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between">
                                <label for="notesInput" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    6. Notes / Clinical Remarks <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                                </label>
                                <span class="text-[11px] text-slate-400">Kit lot numbers, reagent strips, behavioral observations</span>
                            </div>
                            <textarea name="notes" id="notesInput" rows="4"
                                placeholder="Enter specific observations, multi-panel rapid drug screen strip details (e.g. THC, COC, OPI, MET, AMP), specimen physical attributes, or officer remarks..."
                                class="w-full p-4 bg-white border border-slate-300 rounded-2xl text-xs text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all placeholder:text-slate-400 leading-relaxed">{{ old('notes') }}</textarea>
                        </div>

                        <!-- SUBMIT / ACTIONS BAR -->
                        <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <a href="{{ $screeningBaseUrl }}"
                                class="w-full sm:w-auto px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition-colors text-center cursor-pointer">
                                Cancel &amp; Return
                            </a>

                            <button type="submit" id="submitScreeningBtn"
                                class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-extrabold text-xs rounded-2xl shadow-md shadow-teal-600/30 transition-all flex items-center justify-center space-x-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span id="submitScreeningBtnText">Save &amp; Record Drug Screening</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SUSPECT ALREADY SCREENED WARNING MODAL POPUP -->
                <div id="alreadyScreenedModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
                        {{-- Modal Header --}}
                        <div class="px-6 py-4.5 bg-gradient-to-r from-amber-50 to-amber-100/60 border-b border-amber-200/80 flex items-center justify-between">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-mono-code font-extrabold text-amber-950 uppercase tracking-wider">Suspect Already Screened</div>
                                    <div class="text-[10px] text-amber-800 font-medium">Existing Screening Record Found</div>
                                </div>
                            </div>
                            <button type="button" onclick="cancelAlreadyScreenedModal()" class="text-slate-400 hover:text-slate-700 font-bold text-lg p-1 rounded-lg hover:bg-slate-200/50 cursor-pointer">✕</button>
                        </div>

                        {{-- Modal Content --}}
                        <div class="p-6 space-y-4 text-xs">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 leading-snug">
                                    This suspect has already been screened.
                                </h3>
                                <p class="text-slate-500 text-xs mt-1">
                                    Do you want to update/edit or cancel?
                                </p>
                            </div>

                            {{-- Previous Screening Summary Card --}}
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono-code uppercase font-bold text-slate-400">Suspect Name:</span>
                                    <span id="modalScreenedSuspectName" class="font-extrabold text-slate-900 font-mono-code">N/A</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono-code uppercase font-bold text-slate-400">Screening Date:</span>
                                    <span id="modalScreenedDate" class="font-bold text-slate-800 font-mono-code">N/A</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono-code uppercase font-bold text-slate-400">Screening Result:</span>
                                    <span id="modalScreenedResult" class="font-extrabold font-mono-code">N/A</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono-code uppercase font-bold text-slate-400">Substance(s) Found:</span>
                                    <span id="modalScreenedSubstances" class="font-bold text-slate-700 max-w-[200px] truncate text-right">N/A</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono-code uppercase font-bold text-slate-400">On Medication:</span>
                                    <span id="modalScreenedOnMedication" class="font-extrabold font-mono-code text-[11px] text-slate-800">No</span>
                                </div>
                                <div id="modalScreenedMedsRow" class="hidden pt-1.5 border-t border-slate-200/70 text-[11px] text-slate-600">
                                    <strong class="text-teal-800 font-bold">Prescriptions:</strong> <span id="modalScreenedMedsSummary" class="text-slate-700 font-medium">None</span>
                                </div>
                                <div class="pt-2 border-t border-slate-200/70 text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Purpose:</strong> <span id="modalScreenedPurpose">N/A</span>
                                </div>
                                <div class="pt-2 border-t border-slate-200/70 text-[11px] text-slate-500">
                                    <strong class="text-slate-700">Clinical Remarks / Notes:</strong> <span id="modalScreenedNotes" class="italic text-slate-600">N/A</span>
                                </div>
                            </div>

                            <div class="text-[11px] text-amber-800 bg-amber-50/80 p-3 rounded-xl border border-amber-200/80 flex items-start space-x-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Clicking <strong>Update / Edit</strong> will populate the form and allow you to update the existing record in the <code class="font-mono-code bg-amber-100 px-1 rounded text-amber-900 font-bold">drug_screening</code> table.</span>
                            </div>
                        </div>

                        {{-- Modal Buttons --}}
                        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5">
                            <button type="button" onclick="cancelAlreadyScreenedModal()"
                                class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button type="button" onclick="confirmUpdateScreening()"
                                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-teal-600/30 transition-all flex items-center space-x-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Update / Edit</span>
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const screenedSuspects = @json($screenedMap ?? []);
        let currentScreenedRecord = null;
        let lastSelectedSuspectId = '{{ old('suspect_id', '') }}';

        function setPurpose(val) {
            document.getElementById('purposeInput').value = val;
        }

        function addSubstanceTag(tag) {
            const input = document.getElementById('substancesFoundInput');
            if (!input) return;
            const current = input.value.trim();
            if (!current || current === 'No drug(s) detected' || current === 'No substance(s) found') {
                input.value = tag;
            } else {
                const parts = current.split(',').map(s => s.trim().toLowerCase());
                if (!parts.includes(tag.toLowerCase())) {
                    input.value = current + ', ' + tag;
                }
            }
            input.focus();
        }

        function highlightResultCard(val) {
            const cards = {
                'Positive': { id: 'cardPositive', border: 'border-rose-500', bg: 'bg-rose-50/50' },
                'Negative': { id: 'cardNegative', border: 'border-emerald-500', bg: 'bg-emerald-50/50' },
                'Pending':  { id: 'cardPending',  border: 'border-amber-500',  bg: 'bg-amber-50/50' }
            };

            Object.keys(cards).forEach(key => {
                const el = document.getElementById(cards[key].id);
                if (el) {
                    el.classList.remove('border-rose-500', 'border-emerald-500', 'border-amber-500', 'bg-rose-50/50', 'bg-emerald-50/50', 'bg-amber-50/50');
                    el.classList.add('border-slate-200');
                }
            });

            if (cards[val]) {
                const target = document.getElementById(cards[val].id);
                if (target) {
                    target.classList.remove('border-slate-200');
                    target.classList.add(cards[val].border, cards[val].bg);
                }
            }

            const posBox = document.getElementById('positiveSubstanceBox');
            const negLabel = document.getElementById('negativeSubstanceLabel');
            const pendBanner = document.getElementById('pendingSubstanceBanner');
            const subInput = document.getElementById('substancesFoundInput');

            if (val === 'Positive') {
                if (posBox) posBox.classList.remove('hidden');
                if (negLabel) negLabel.classList.add('hidden');
                if (pendBanner) pendBanner.classList.add('hidden');
                if (subInput) {
                    subInput.required = true;
                    if (subInput.value === 'No drug(s) detected' || subInput.value === 'No substance(s) found') {
                        subInput.value = '';
                    }
                    setTimeout(() => subInput.focus(), 60);
                }
            } else if (val === 'Negative') {
                if (posBox) posBox.classList.add('hidden');
                if (negLabel) negLabel.classList.remove('hidden');
                if (pendBanner) pendBanner.classList.add('hidden');
                if (subInput) {
                    subInput.required = false;
                    subInput.value = 'No drug(s) detected';
                }
            } else {
                if (posBox) posBox.classList.add('hidden');
                if (negLabel) negLabel.classList.add('hidden');
                if (pendBanner) pendBanner.classList.remove('hidden');
                if (subInput) {
                    subInput.required = false;
                    if (subInput.value === 'No drug(s) detected' || subInput.value === 'No substance(s) found') {
                        subInput.value = '';
                    }
                }
            }
        }

        function resolveClientMugshotUrl(src) {
            if (!src || src === 'null' || src === 'undefined' || src === '#' || src === 'N/A') return '';
            src = String(src).trim();
            if (src === '' || src === '#' || src === 'null' || src === 'undefined' || src === 'N/A') return '';
            if (src.startsWith('data:') || src.startsWith('http://') || src.startsWith('https://')) {
                return src;
            }
            const clean = src.replace(/^\/+/, '');
            if (clean.startsWith('storage/')) {
                return '{{ asset('') }}' + clean;
            }
            return '{{ asset('storage') }}/' + clean;
        }

        function renderCivilianPreview(civilianName) {
            const previewCard = document.getElementById('suspectPreviewCard');
            if (!previewCard) return;

            const name = (civilianName || '').trim();
            if (!name) {
                previewCard.classList.add('hidden');
                return;
            }

            document.getElementById('previewName').textContent = name + ' (Civilian)';
            const detailsEl = document.getElementById('previewDetails');
            if (detailsEl) {
                detailsEl.innerHTML = '<span>Status: <strong class="text-teal-700 font-semibold">Civilian / Non-Suspect</strong></span> &bull; <span>Assay: <strong class="text-slate-700">Walk-in Screening</strong></span>';
            }
            document.getElementById('previewCaseNo').textContent = 'Civilian Intake';

            const badge = document.getElementById('previewReasonBadge');
            if (badge) {
                badge.className = 'mt-1 inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-teal-100 text-teal-800 border border-teal-200';
                badge.textContent = 'Civilian Person';
            }

            const avatarContainer = document.getElementById('previewAvatarContainer');
            if (avatarContainer) {
                const initials = name.split(/\s+/).map(p => p[0]).join('').substring(0, 2).toUpperCase() || 'CV';
                avatarContainer.className = 'w-12 h-12 rounded-xl bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center border border-teal-200 shrink-0 font-mono-code text-sm';
                avatarContainer.innerHTML = initials;
            }

            previewCard.classList.remove('hidden');
        }

        function renderSuspectPreview() {
            const selectEl = document.getElementById('suspectSelect');
            const previewCard = document.getElementById('suspectPreviewCard');
            if (!selectEl || !selectEl.value) {
                if (previewCard) previewCard.classList.add('hidden');
                return;
            }

            const val = selectEl.value;
            if (val.startsWith('civilian:') || isNaN(val)) {
                const civName = val.startsWith('civilian:') ? val.substring(9) : val;
                renderCivilianPreview(civName);
                return;
            }

            const opt = selectEl.options[selectEl.selectedIndex];
            if (!opt || !opt.value) {
                if (previewCard) previewCard.classList.add('hidden');
                return;
            }

            const name = opt.getAttribute('data-name') || 'Suspect';
            const alias = opt.getAttribute('data-alias');
            const card = opt.getAttribute('data-card') || 'N/A';
            const facility = opt.getAttribute('data-facility') || 'NACOC Holding Center';
            const caseNo = opt.getAttribute('data-case') || 'N/A';
            const mugshot = opt.getAttribute('data-mugshot');
            const reason = opt.getAttribute('data-reason') || 'Registered Suspect';

            document.getElementById('previewName').textContent = name + (alias && alias !== 'N/A' ? ' ("' + alias + '")' : '');
            const detailsEl = document.getElementById('previewDetails');
            if (detailsEl) {
                detailsEl.innerHTML = '<span>Ghana Card: <strong id="previewCardNo" class="font-mono-code text-slate-700">' + card + '</strong></span> &bull; <span>Facility: <strong id="previewFacility" class="text-slate-700">' + facility + '</strong></span>';
            }
            document.getElementById('previewCaseNo').textContent = caseNo;

            const badge = document.getElementById('previewReasonBadge');
            if (badge) {
                badge.className = 'mt-1 inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-200';
                badge.textContent = reason;
            }

            const avatarContainer = document.getElementById('previewAvatarContainer');
            if (avatarContainer) {
                avatarContainer.className = 'w-12 h-12 rounded-xl bg-blue-100 text-blue-800 font-extrabold flex items-center justify-center border border-blue-200 shrink-0 font-mono-code text-sm overflow-hidden';
                const resolvedMugshot = resolveClientMugshotUrl(mugshot);
                const initials = (name.substring(0, 2) || 'SP').toUpperCase();
                if (resolvedMugshot) {
                    avatarContainer.innerHTML = '<img src="' + resolvedMugshot + '" class="w-full h-full object-cover rounded-xl" alt="' + name + '" onerror="this.onerror=null; this.parentElement.innerHTML=\'' + initials + '\';">';
                } else {
                    avatarContainer.innerHTML = initials;
                }
            }

            if (previewCard) previewCard.classList.remove('hidden');
        }

        function handleSuspectChange(selectEl) {
            if (!selectEl || !selectEl.value) {
                renderSuspectPreview();
                return;
            }

            const suspectId = selectEl.value;
            const currentEditingScreeningId = document.getElementById('screeningIdInput').value;

            // Check if suspect has already been screened
            if (screenedSuspects && screenedSuspects[suspectId]) {
                const record = screenedSuspects[suspectId];

                // If already confirmed in edit mode for this screening, don't re-trigger popup
                if (currentEditingScreeningId && String(currentEditingScreeningId) === String(record.screening_id)) {
                    renderSuspectPreview();
                    return;
                }

                // Show Suspect Preview in background
                renderSuspectPreview();

                // Open Pop-up Modal
                openAlreadyScreenedModal(record);
                return;
            }

            // If choosing a suspect that is NOT already screened, clear update mode if previously set
            if (currentEditingScreeningId) {
                cancelUpdateModeQuietly();
            }

            lastSelectedSuspectId = suspectId;
            renderSuspectPreview();
        }

        function openAlreadyScreenedModal(record) {
            currentScreenedRecord = record;

            // Populate modal fields
            const nameEl = document.getElementById('modalScreenedSuspectName');
            if (nameEl) {
                nameEl.textContent = record.suspect_name + (record.suspect_alias ? ' ("' + record.suspect_alias + '")' : '');
            }

            const dateEl = document.getElementById('modalScreenedDate');
            if (dateEl) {
                dateEl.textContent = record.screening_date_formatted || record.screening_date || 'N/A';
            }

            const resultEl = document.getElementById('modalScreenedResult');
            if (resultEl) {
                let badgeClass = 'text-amber-800 bg-amber-100 border-amber-300';
                if (record.result === 'Positive') {
                    badgeClass = 'text-rose-700 bg-rose-100 border-rose-300';
                } else if (record.result === 'Negative') {
                    badgeClass = 'text-emerald-700 bg-emerald-100 border-emerald-300';
                }
                resultEl.className = 'font-extrabold font-mono-code px-2 py-0.5 rounded-md border text-[11px] ' + badgeClass;
                resultEl.textContent = record.result || 'Pending';
            }

            const subEl = document.getElementById('modalScreenedSubstances');
            if (subEl) {
                subEl.textContent = record.substances_found || (record.result === 'Negative' ? 'No drug(s) detected' : 'None specified');
            }

            const purpEl = document.getElementById('modalScreenedPurpose');
            if (purpEl) {
                purpEl.textContent = record.purpose || 'N/A';
            }

            const notesEl = document.getElementById('modalScreenedNotes');
            if (notesEl) {
                notesEl.textContent = record.notes || record.note || 'No clinical remarks recorded.';
            }

            // Populate Medication information in modal preview
            const onMedEl = document.getElementById('modalScreenedOnMedication');
            const medsRow = document.getElementById('modalScreenedMedsRow');
            const summaryEl = document.getElementById('modalScreenedMedsSummary');
            if (onMedEl) {
                if (record.on_medication) {
                    onMedEl.textContent = 'Yes (On Medication)';
                    onMedEl.className = 'font-extrabold font-mono-code text-[11px] text-teal-800 bg-teal-100/80 px-2 py-0.5 rounded-md border border-teal-200';
                    if (medsRow) medsRow.classList.remove('hidden');
                    if (summaryEl) summaryEl.textContent = record.medication_summary || 'Active medication(s) recorded.';
                } else {
                    onMedEl.textContent = 'No';
                    onMedEl.className = 'font-bold font-mono-code text-[11px] text-slate-600';
                    if (medsRow) medsRow.classList.add('hidden');
                }
            }

            const modal = document.getElementById('alreadyScreenedModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function cancelAlreadyScreenedModal() {
            const modal = document.getElementById('alreadyScreenedModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            $('#suspectSelect').val(lastSelectedSuspectId || '').trigger('change.select2');

            currentScreenedRecord = null;
            renderSuspectPreview();
        }

        function confirmUpdateScreening() {
            const modal = document.getElementById('alreadyScreenedModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            if (!currentScreenedRecord) return;

            // 1. Populate Hidden Screening ID
            document.getElementById('screeningIdInput').value = currentScreenedRecord.screening_id;

            // 2. Populate Medication details
            if (currentScreenedRecord.on_medication) {
                toggleMedicationSection(true);
                const container = document.getElementById('medicationsContainer');
                if (container) container.innerHTML = '';
                medicationCount = 0;
                if (currentScreenedRecord.medications && currentScreenedRecord.medications.length > 0) {
                    currentScreenedRecord.medications.forEach(m => addMedicationRow(m));
                } else {
                    addMedicationRow();
                }
            } else {
                toggleMedicationSection(false);
                const container = document.getElementById('medicationsContainer');
                if (container) container.innerHTML = '';
                medicationCount = 0;
            }

            // 3. Populate Purpose
            if (currentScreenedRecord.purpose) {
                document.getElementById('purposeInput').value = currentScreenedRecord.purpose;
            }

            // 4. Populate Date of Screening
            if (currentScreenedRecord.screening_date) {
                document.getElementById('screeningDateInput').value = currentScreenedRecord.screening_date;
            }

            // 5. Select Result and highlight card
            const resultVal = currentScreenedRecord.result || 'Pending';
            const radio = document.querySelector('input[name="result"][value="' + resultVal + '"]');
            if (radio) {
                radio.checked = true;
            }
            highlightResultCard(resultVal);

            // Populate Substances Found
            const subInput = document.getElementById('substancesFoundInput');
            if (subInput) {
                if (resultVal === 'Positive') {
                    subInput.value = currentScreenedRecord.substances_found || '';
                } else if (resultVal === 'Negative') {
                    subInput.value = 'No drug(s) detected';
                } else {
                    subInput.value = currentScreenedRecord.substances_found || '';
                }
            }

            // 6. Populate Notes
            const existingNotes = currentScreenedRecord.notes || currentScreenedRecord.note || '';
            if (existingNotes) {
                document.getElementById('notesInput').value = existingNotes;
            }

            // 8. Show Update Mode Banner
            const banner = document.getElementById('updateModeBanner');
            if (banner) {
                banner.classList.remove('hidden');
            }
            const bannerId = document.getElementById('updateBannerScreeningId');
            if (bannerId) {
                bannerId.textContent = String(currentScreenedRecord.screening_id).padStart(3, '0');
            }
            const bannerName = document.getElementById('updateBannerSuspectName');
            if (bannerName) {
                bannerName.textContent = currentScreenedRecord.suspect_name;
            }

            // 9. Update Submit Button Label
            const submitText = document.getElementById('submitScreeningBtnText');
            if (submitText) {
                submitText.textContent = 'Update & Save Drug Screening Record';
            }

            lastSelectedSuspectId = currentScreenedRecord.suspect_id;
            currentScreenedRecord = null;

            // Scroll to purpose input smoothly
            const purposeInput = document.getElementById('purposeInput');
            if (purposeInput) {
                purposeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => purposeInput.focus(), 300);
            }
        }

        function cancelUpdateModeQuietly() {
            document.getElementById('screeningIdInput').value = '';
            const banner = document.getElementById('updateModeBanner');
            if (banner) banner.classList.add('hidden');
            const submitText = document.getElementById('submitScreeningBtnText');
            if (submitText) submitText.textContent = 'Save & Record Drug Screening';
        }

        function cancelUpdateMode() {
            cancelUpdateModeQuietly();

            $('#suspectSelect').val('').trigger('change.select2');
            const civInput = document.getElementById('civilianNameInput');
            if (civInput) civInput.value = '';
            lastSelectedSuspectId = '';
            currentScreenedRecord = null;

            document.getElementById('purposeInput').value = '';
            document.getElementById('screeningDateInput').value = new Date().toISOString().split('T')[0];

            const pendingRadio = document.querySelector('input[name="result"][value="Pending"]');
            if (pendingRadio) {
                pendingRadio.checked = true;
            }
            highlightResultCard('Pending');

            const subInput = document.getElementById('substancesFoundInput');
            if (subInput) subInput.value = '';

            document.getElementById('notesInput').value = '';

            toggleMedicationSection(false);
            const container = document.getElementById('medicationsContainer');
            if (container) container.innerHTML = '';
            medicationCount = 0;

            renderSuspectPreview();
        }

        // ==========================================
        // MEDICATION DYNAMIC ROWS & TOGGLE LOGIC
        // ==========================================
        let medicationCount = 0;

        function toggleMedicationSection(isYes) {
            const radioNo = document.querySelector('input[name="on_medication"][value="0"]');
            const radioYes = document.querySelector('input[name="on_medication"][value="1"]');
            const labelNo = document.getElementById('medicationOptionNoLabel');
            const labelYes = document.getElementById('medicationOptionYesLabel');
            const section = document.getElementById('medicationInfoSection');

            if (isYes) {
                if (radioYes) radioYes.checked = true;
                if (labelYes) {
                    labelYes.className = 'flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-teal-600 text-white shadow-2xs';
                }
                if (labelNo) {
                    labelNo.className = 'flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-slate-600 hover:text-slate-900';
                }
                if (section) section.classList.remove('hidden');

                const container = document.getElementById('medicationsContainer');
                if (container && container.querySelectorAll('.medication-entry-card').length === 0) {
                    addMedicationRow();
                }
            } else {
                if (radioNo) radioNo.checked = true;
                if (labelNo) {
                    labelNo.className = 'flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all bg-white text-slate-900 shadow-2xs';
                }
                if (labelYes) {
                    labelYes.className = 'flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition-all text-slate-600 hover:text-slate-900';
                }
                if (section) section.classList.add('hidden');
            }
        }

        function addMedicationRow(data = {}) {
            const container = document.getElementById('medicationsContainer');
            if (!container) return;

            const idx = medicationCount++;
            const rowId = 'med_row_' + idx;

            const medName = data.medication_name || '';
            const dosage = data.dosage || '';
            const formVal = data.form || '';
            const freqVal = data.frequency || '';
            const routeVal = data.route || '';
            const sourceVal = data.prescription_source || '';
            const dateStarted = data.date_started || '';
            const duration = data.duration_ongoing || '';
            const lastDose = data.last_dose_taken || '';

            const card = document.createElement('div');
            card.id = rowId;
            card.className = 'medication-entry-card p-4.5 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-3 transition-all relative';

            card.innerHTML = `
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        <span class="font-mono-code text-[11px] font-black uppercase text-slate-800 tracking-wider">
                            Medication Entry #<span class="med-index-num">1</span>
                        </span>
                    </div>
                    <button type="button" onclick="removeMedicationRow('${rowId}')"
                        class="px-2.5 py-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition-all flex items-center space-x-1 cursor-pointer"
                        title="Remove this medication entry">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Remove</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- Medication Name -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Medication Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="medications[${idx}][medication_name]" value="${escapeHtml(medName)}" required
                            placeholder="e.g. Paracetamol, Amoxicillin, Diazepam"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all">
                    </div>

                    <!-- Dosage / Strength -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Dosage / Strength
                        </label>
                        <input type="text" name="medications[${idx}][dosage]" value="${escapeHtml(dosage)}"
                            placeholder="e.g. 500 mg, 10 ml, 20 mcg"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all">
                    </div>

                    <!-- Form (Select2 tags: true) -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Form
                        </label>
                        <select name="medications[${idx}][form]" class="select2-medication-field select2-tag-field w-full" data-placeholder="Select or type form...">
                            <option value=""></option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Patch">Patch</option>
                            <option value="Inhaler">Inhaler</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- Frequency (Select2 tags: true) -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Frequency
                        </label>
                        <select name="medications[${idx}][frequency]" class="select2-medication-field select2-tag-field w-full" data-placeholder="Select or type frequency...">
                            <option value=""></option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="Three times daily">Three times daily</option>
                            <option value="Four times daily">Four times daily</option>
                            <option value="As needed">As needed</option>
                            <option value="Every 8 hours">Every 8 hours</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>

                    <!-- Route / Intake (Select2 tags: true) -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Route / Intake
                        </label>
                        <select name="medications[${idx}][route]" class="select2-medication-field select2-tag-field w-full" data-placeholder="Select or type route...">
                            <option value=""></option>
                            <option value="Oral">Oral</option>
                            <option value="Intravenous">Intravenous</option>
                            <option value="Topical">Topical</option>
                            <option value="Inhaled">Inhaled</option>
                            <option value="Intramuscular">Intramuscular</option>
                            <option value="Sublingual">Sublingual</option>
                        </select>
                    </div>

                    <!-- Prescription Source -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Prescription Source
                        </label>
                        <select name="medications[${idx}][prescription_source]"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all cursor-pointer">
                            <option value="">-- Select Source --</option>
                            <option value="doctor" ${sourceVal === 'doctor' ? 'selected' : ''}>Doctor</option>
                            <option value="otc" ${sourceVal === 'otc' ? 'selected' : ''}>Bought over the counter</option>
                            <option value="self_administered" ${sourceVal === 'self_administered' ? 'selected' : ''}>Self-administered</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- Date Started -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Date Started
                        </label>
                        <input type="date" name="medications[${idx}][date_started]" value="${escapeHtml(dateStarted)}"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all">
                    </div>

                    <!-- Duration / Ongoing -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Duration / Ongoing
                        </label>
                        <input type="text" name="medications[${idx}][duration_ongoing]" value="${escapeHtml(duration)}"
                            placeholder="e.g. 2 weeks, Ongoing"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all">
                    </div>

                    <!-- Last Dose Taken -->
                    <div class="space-y-1">
                        <label class="block text-[10.5px] font-extrabold text-slate-700 uppercase font-mono-code">
                            Last Dose Taken
                        </label>
                        <input type="datetime-local" name="medications[${idx}][last_dose_taken]" value="${escapeHtml(lastDose)}"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition-all">
                    </div>
                </div>
            `;

            container.appendChild(card);

            // Initialize Select2 on taggable fields
            const $card = $(card);
            $card.find('.select2-tag-field').each(function () {
                const $el = $(this);
                const placeholder = $el.attr('data-placeholder') || 'Select or type...';
                const nameAttr = $el.attr('name') || '';

                let valToSet = '';
                if (nameAttr.includes('[form]')) valToSet = formVal;
                else if (nameAttr.includes('[frequency]')) valToSet = freqVal;
                else if (nameAttr.includes('[route]')) valToSet = routeVal;

                if (valToSet) {
                    if ($el.find('option[value="' + valToSet.replace(/"/g, '\\"') + '"]').length === 0) {
                        $el.append(new Option(valToSet, valToSet, true, true));
                    } else {
                        $el.val(valToSet);
                    }
                }

                $el.select2({
                    tags: true,
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: true
                });
            });

            updateMedicationIndexes();
        }

        function removeMedicationRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                $(row).find('.select2-tag-field').each(function () {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });
                row.remove();
            }
            updateMedicationIndexes();
        }

        function updateMedicationIndexes() {
            const cards = document.querySelectorAll('#medicationsContainer .medication-entry-card');
            cards.forEach((card, i) => {
                const numEl = card.querySelector('.med-index-num');
                if (numEl) numEl.textContent = i + 1;
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Initialize state on page load
        $(document).ready(function () {
            const $suspectSelect = $('#suspectSelect');

            $suspectSelect.select2({
                tags: true,
                placeholder: '-- Search Suspect Name or Type New Civilian Name --',
                allowClear: true,
                width: '100%',
                escapeMarkup: function (m) {
                    return m;
                },
                insertTag: function (data, tag) {
                    // Critical: Put the new civilian tag at the VERY TOP of the results so it is highlighted first
                    data.unshift(tag);
                },
                createTag: function (params) {
                    const term = String(params.term || '').trim();
                    if (term === '' || term.length < 2) {
                        return null;
                    }
                    // Don't create tag if exact match for an existing suspect's name exists
                    let exactMatchFound = false;
                    $suspectSelect.find('option').each(function () {
                        const optName = $(this).attr('data-name') || $(this).text().replace(/\s*\(Suspect\)|\s*\(Civilian\)/gi, '').trim();
                        if (optName.toLowerCase() === term.toLowerCase()) {
                            exactMatchFound = true;
                            return false;
                        }
                    });
                    if (exactMatchFound) {
                        return null;
                    }
                    return {
                        id: 'civilian:' + term,
                        text: term,
                        civilianName: term,
                        isCivilian: true,
                        newTag: true
                    };
                },
                matcher: function (params, data) {
                    const term = String(params.term || '').trim().toLowerCase();
                    if (term === '') {
                        return data;
                    }
                    if (data.newTag || (data.id && String(data.id).startsWith('civilian:'))) {
                        return data;
                    }
                    if (!data.id) {
                        return null;
                    }

                    const opt = data.element;
                    const name = (opt ? opt.getAttribute('data-name') : '') || data.text.replace(/\s*\(Suspect\)|\s*\(Civilian\)/gi, '').trim();
                    const alias = opt ? (opt.getAttribute('data-alias') || '') : '';
                    const card = opt ? (opt.getAttribute('data-card') || '') : '';
                    const caseNo = opt ? (opt.getAttribute('data-case') || '') : '';

                    const haystack = (name + ' ' + alias + ' ' + card + ' ' + caseNo).toLowerCase();

                    if (haystack.indexOf(term) > -1) {
                        return data;
                    }

                    return null;
                },
                templateResult: function (data) {
                    if (!data.id) {
                        return data.text;
                    }

                    const idStr = String(data.id);
                    const isCiv = data.newTag || data.isCivilian || idStr.startsWith('civilian:');

                    if (isCiv) {
                        const rawName = (data.civilianName || data.text || '').replace(/^civilian:/i, '').replace(/\s*\(\+ Add\s*|\s*\(Civilian\)\s*|\+ Add\s*|"/gi, '').trim();
                        return $(
                            '<div class="civilian-tag-option flex items-center justify-between p-2.5 rounded-xl bg-gradient-to-r from-teal-50 to-emerald-50 border-2 border-teal-500 text-teal-950 shadow-xs cursor-pointer transition-all">' +
                                '<div class="flex items-center space-x-3 min-w-0">' +
                                    '<div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center font-extrabold text-base shrink-0 shadow-xs">' +
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>' +
                                    '</div>' +
                                    '<div class="min-w-0">' +
                                        '<div class="font-extrabold text-xs text-teal-950 flex items-center gap-1.5">' +
                                            '<span>Add "<strong>' + $('<div>').text(rawName).html() + '</strong>"</span>' +
                                        '</div>' +
                                        '<div class="text-[10.5px] text-teal-700 font-medium">Record screening as a new Civilian Person</div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="text-right shrink-0 ml-3">' +
                                    '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono-code font-extrabold bg-teal-600 text-white shadow-2xs">' +
                                        '+ NEW CIVILIAN' +
                                    '</span>' +
                                    '<div class="text-[9.5px] font-mono-code text-teal-700 font-bold mt-0.5">Press Enter ↵</div>' +
                                '</div>' +
                            '</div>'
                        );
                    }

                    const opt = data.element;
                    if (opt) {
                        const name = opt.getAttribute('data-name') || data.text.replace(/\s*\(Suspect\)/gi, '').trim();
                        const alias = opt.getAttribute('data-alias');
                        const caseNo = opt.getAttribute('data-case') || 'N/A';
                        const card = opt.getAttribute('data-card') || 'N/A';
                        const mugshot = opt.getAttribute('data-mugshot');
                        const initials = (name.substring(0, 2) || 'SP').toUpperCase();
                        const resolvedMugshot = resolveClientMugshotUrl(mugshot);

                        let avatarHtml = '<div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center font-extrabold text-xs uppercase font-mono-code shrink-0">' + initials + '</div>';
                        if (resolvedMugshot) {
                            avatarHtml = '<div class="w-8 h-8 rounded-xl overflow-hidden shrink-0 border border-slate-200 bg-slate-100 flex items-center justify-center"><img src="' + resolvedMugshot + '" class="w-full h-full object-cover" onerror="this.onerror=null; this.parentElement.innerHTML=\'' + initials + '\';"></div>';
                        }

                        return $(
                            '<div class="suspect-item-option flex items-center justify-between p-2 rounded-xl transition-all cursor-pointer">' +
                                '<div class="flex items-center space-x-2.5 min-w-0">' +
                                    avatarHtml +
                                    '<div class="min-w-0">' +
                                        '<div class="font-extrabold text-xs text-slate-900 truncate flex items-center gap-1.5">' +
                                            '<span>' + $('<div>').text(name).html() + '</span>' +
                                            (alias && alias !== 'N/A' ? '<span class="text-slate-400 font-normal italic text-[11px]">("' + $('<div>').text(alias).html() + '")</span>' : '') +
                                        '</div>' +
                                        '<div class="text-[10px] text-slate-500 font-mono-code flex items-center gap-2 truncate mt-0.5">' +
                                            '<span>Case: <strong class="text-slate-700 font-semibold">' + caseNo + '</strong></span>' +
                                            '<span>&bull;</span>' +
                                            '<span>Card: <strong class="text-slate-700 font-semibold">' + card + '</strong></span>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                                '<span class="px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-blue-50 text-blue-800 border border-blue-200 shrink-0 ml-2">' +
                                    'SUSPECT' +
                                '</span>' +
                            '</div>'
                        );
                    }

                    return data.text;
                },
                templateSelection: function (data) {
                    if (!data.id) {
                        return data.text || '-- Search Suspect Name or Type New Civilian Name --';
                    }
                    const idStr = String(data.id);
                    const isCiv = data.newTag || data.isCivilian || idStr.startsWith('civilian:') || (data.element && data.element.getAttribute('data-civilian') === 'true');

                    if (isCiv) {
                        let cleanName = (data.civilianName || data.text || '').replace(/^civilian:/i, '').replace(/\s*\(Civilian\)/gi, '').trim();
                        return $(
                            '<span class="inline-flex items-center gap-2 font-extrabold text-slate-900 text-xs truncate">' +
                                '<span class="w-2 h-2 rounded-full bg-teal-500 shrink-0"></span>' +
                                '<span class="truncate">' + $('<div>').text(cleanName).html() + '</span>' +
                                '<span class="px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-teal-100 text-teal-800 border border-teal-200 shrink-0 ml-1">Civilian</span>' +
                            '</span>'
                        );
                    }

                    const opt = data.element;
                    const suspectName = opt ? (opt.getAttribute('data-name') || data.text.replace(/\s*\(Suspect\)/gi, '').trim()) : data.text.replace(/\s*\(Suspect\)/gi, '').trim();
                    const optMugshot = opt ? resolveClientMugshotUrl(opt.getAttribute('data-mugshot')) : '';
                    let thumbHtml = '<span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>';
                    if (optMugshot) {
                        thumbHtml = '<img src="' + optMugshot + '" class="w-4 h-4 rounded-full object-cover border border-slate-200 shrink-0" onerror="this.style.display=\'none\';">';
                    }

                    return $(
                        '<span class="inline-flex items-center gap-2 font-extrabold text-slate-900 text-xs truncate">' +
                            thumbHtml +
                            '<span class="truncate">' + $('<div>').text(suspectName).html() + '</span>' +
                            '<span class="px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-200 shrink-0 ml-1">Suspect</span>' +
                        '</span>'
                    );
                }
            });

            $suspectSelect.on('select2:open', function () {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.placeholder = 'Type to search suspects or enter a new civilian name...';
                }
            });

            $suspectSelect.on('select2:clear', function () {
                const civilianInput = document.getElementById('civilianNameInput');
                if (civilianInput) civilianInput.value = '';
                cancelUpdateModeQuietly();
                renderSuspectPreview();
            });

            $suspectSelect.on('change', function () {
                const val = $(this).val();
                const civilianInput = document.getElementById('civilianNameInput');

                if (!val) {
                    if (civilianInput) civilianInput.value = '';
                    renderSuspectPreview();
                    return;
                }

                if (val.startsWith('civilian:') || isNaN(val)) {
                    const civName = val.startsWith('civilian:') ? val.substring(9) : val;
                    const cleanName = civName.replace(/\s*\(Civilian\)/gi, '').trim();
                    if (civilianInput) civilianInput.value = cleanName;

                    cancelUpdateModeQuietly();
                    renderCivilianPreview(cleanName);
                } else {
                    if (civilianInput) civilianInput.value = '';
                    handleSuspectChange(this);
                }
            });

            // Form validation on submit
            $('#screeningForm').on('submit', function (e) {
                const sVal = $suspectSelect.val();
                const cVal = $('#civilianNameInput').val();
                if (!sVal && !cVal) {
                    e.preventDefault();
                    alert('Please select a registered suspect or type a civilian name.');
                    $suspectSelect.select2('open');
                    return false;
                }

                // Verify medication entries if On Medication is selected
                const isOnMed = $('input[name="on_medication"]:checked').val() === '1';
                if (isOnMed) {
                    const medInputs = $('#medicationsContainer input[name*="[medication_name]"]');
                    let validCount = 0;
                    medInputs.each(function () {
                        if ($(this).val().trim() !== '') {
                            validCount++;
                        }
                    });
                    if (validCount === 0) {
                        e.preventDefault();
                        alert('Please enter at least one Medication Name, or change "On Medication" to No.');
                        if (medInputs.length > 0) {
                            medInputs.first().focus();
                        } else {
                            addMedicationRow();
                        }
                        return false;
                    }
                }
            });

            const checkedResult = document.querySelector('input[name="result"]:checked');
            if (checkedResult) {
                highlightResultCard(checkedResult.value);
            } else {
                highlightResultCard('Pending');
            }

            // Restore initial old medication state if returning from validation error
            const initialOnMedication = @json(old('on_medication', false));
            const initialOldMeds = @json(old('medications', []));

            if (initialOnMedication == '1' || initialOnMedication === true || (Array.isArray(initialOldMeds) && initialOldMeds.length > 0)) {
                toggleMedicationSection(true);
                if (Array.isArray(initialOldMeds) && initialOldMeds.length > 0) {
                    const container = document.getElementById('medicationsContainer');
                    if (container) container.innerHTML = '';
                    medicationCount = 0;
                    initialOldMeds.forEach(m => addMedicationRow(m));
                }
            }

            const initialVal = $suspectSelect.val();
            if (initialVal) {
                lastSelectedSuspectId = initialVal;
                $suspectSelect.trigger('change');

                const existingScreeningId = document.getElementById('screeningIdInput').value;
                if (existingScreeningId) {
                    const submitText = document.getElementById('submitScreeningBtnText');
                    if (submitText) submitText.textContent = 'Update & Save Drug Screening Record';
                }
            }
        });
    </script>
</body>

</html>

