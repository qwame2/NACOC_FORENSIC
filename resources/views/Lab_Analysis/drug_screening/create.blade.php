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

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>

<body class="min-h-screen flex antialiased bg-slate-50 text-slate-800 selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        <!-- SHARED SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3">
                    <a href="{{ url('/Lab_Analysis/drug-screening') }}"
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
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 p-6 rounded-3xl text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 bg-teal-500 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">LAB INTAKE</span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs font-bold text-teal-400 font-mono-code">NEW DRUG SCREENING</span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-white flex items-center gap-3">
                                <span>Record Suspect Drug Screening</span>
                            </h1>
                            <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                Submit a new biological or chemical drug screening record for an eligible suspect. Eligible candidates include suspects linked to drug commodities or those with no exhibits lodged yet.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="text-right">
                                <div class="text-[10px] font-mono-code uppercase text-slate-400">Supervising Chemist</div>
                                <div class="text-xs font-bold text-teal-300 font-mono-code">{{ $headName }}</div>
                            </div>
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

                    <form id="screeningForm" action="{{ url('/Lab_Analysis/drug-screening') }}" method="POST" class="p-8 space-y-6">
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

                        <!-- 1. SUSPECT DROPDOWN -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="suspectSelect" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    1. Suspect Name &amp; Profile <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500">All registered suspects in database</span>
                            </div>

                            <div class="relative">
                                <select name="suspect_id" id="suspectSelect" required onchange="handleSuspectChange(this)"
                                    class="w-full pl-4 pr-10 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all cursor-pointer">
                                    <option value="" disabled {{ old('suspect_id') ? '' : 'selected' }}>
                                        -- Select Suspect Name --
                                    </option>
                                    @foreach(($suspects ?? $eligibleSuspects) as $suspect)
                                    @php
                                        $caseNo = $suspect->case_number ?? ($suspect->singleCase->case_number ?? ($suspect->multipleCase->case_number ?? 'N/A'));
                                    @endphp
                                    <option value="{{ $suspect->id }}"
                                        {{ old('suspect_id') == $suspect->id ? 'selected' : '' }}
                                        data-name="{{ $suspect->suspect_name }}"
                                        data-alias="{{ $suspect->suspect_alias ?? 'N/A' }}"
                                        data-card="{{ $suspect->ghana_card ?? 'N/A' }}"
                                        data-gender="{{ $suspect->gender ?? 'Male' }}"
                                        data-facility="{{ $suspect->holding_facility ?? 'NACOC Holding Center' }}"
                                        data-case="{{ $caseNo }}"
                                        data-mugshot="{{ $suspect->mugshot_front ?? '' }}"
                                        data-reason="Active Suspect">
                                        {{ $suspect->suspect_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- DYNAMIC SUSPECT PREVIEW CARD -->
                            <div id="suspectPreviewCard" class="hidden mt-3 p-4 bg-slate-50 rounded-2xl border border-slate-200 transition-all">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center space-x-3.5">
                                        <div id="previewAvatarContainer" class="w-12 h-12 rounded-xl bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center border border-teal-200 shrink-0 font-mono-code text-sm">
                                            SP
                                        </div>
                                        <div>
                                            <div id="previewName" class="font-extrabold text-sm text-slate-900">Suspect Name</div>
                                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span>Ghana Card: <strong id="previewCardNo" class="font-mono-code text-slate-700">N/A</strong></span>
                                                <span>&bull;</span>
                                                <span>Facility: <strong id="previewFacility" class="text-slate-700">N/A</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div id="previewCaseNo" class="text-xs font-mono-code font-extrabold text-slate-900">NCC-2026</div>
                                        <span id="previewReasonBadge" class="mt-1 inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            Eligible Profile
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. PURPOSE & DATE OF SCREENING (2 COLUMNS) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- PURPOSE OF SCREENING -->
                            <div class="space-y-2">
                                <label for="purposeInput" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    2. Purpose of Screening <span class="text-rose-500">*</span>
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
                                    3. Date of Screening <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="screening_date" id="screeningDateInput" required
                                    value="{{ old('screening_date', date('Y-m-d')) }}"
                                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-mono-code font-bold text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all cursor-pointer">
                                <p class="text-[11px] text-slate-400">Date the biological/urine sample was obtained or assayed.</p>
                            </div>
                        </div>

                        <!-- 3. RESULTS / STATUS SELECTION -->
                        <div class="space-y-3">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                4. Screening Outcome / Results <span class="text-rose-500">*</span>
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

                        <!-- 4. NOTES / REMARKS -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="notesInput" class="block text-xs font-extrabold text-slate-800 uppercase font-mono-code tracking-wider">
                                    5. Notes / Clinical Remarks <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                                </label>
                                <span class="text-[11px] text-slate-400">Kit lot numbers, reagent strips, behavioral observations</span>
                            </div>
                            <textarea name="notes" id="notesInput" rows="4"
                                placeholder="Enter specific observations, multi-panel rapid drug screen strip details (e.g. THC, COC, OPI, MET, AMP), specimen physical attributes, or officer remarks..."
                                class="w-full p-4 bg-white border border-slate-300 rounded-2xl text-xs text-slate-900 focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all placeholder:text-slate-400 leading-relaxed">{{ old('notes') }}</textarea>
                        </div>

                        <!-- SUBMIT / ACTIONS BAR -->
                        <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <a href="{{ url('/Lab_Analysis/drug-screening') }}"
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

        function renderSuspectPreview() {
            const selectEl = document.getElementById('suspectSelect');
            const previewCard = document.getElementById('suspectPreviewCard');
            if (!selectEl || !selectEl.value) {
                if (previewCard) previewCard.classList.add('hidden');
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
            const reason = opt.getAttribute('data-reason') || 'Eligible Profile';

            document.getElementById('previewName').textContent = name + (alias && alias !== 'N/A' ? ' ("' + alias + '")' : '');
            document.getElementById('previewCardNo').textContent = card;
            document.getElementById('previewFacility').textContent = facility;
            document.getElementById('previewCaseNo').textContent = caseNo;

            const badge = document.getElementById('previewReasonBadge');
            if (badge) badge.textContent = reason;

            const avatarContainer = document.getElementById('previewAvatarContainer');
            if (avatarContainer) {
                if (mugshot) {
                    avatarContainer.innerHTML = '<img src="' + mugshot + '" class="w-full h-full object-cover rounded-xl" alt="' + name + '">';
                } else {
                    avatarContainer.innerHTML = (name.substring(0, 2) || 'SP').toUpperCase();
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

            const selectEl = document.getElementById('suspectSelect');
            if (selectEl) {
                selectEl.value = lastSelectedSuspectId || '';
            }

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

            // 2. Populate Purpose
            if (currentScreenedRecord.purpose) {
                document.getElementById('purposeInput').value = currentScreenedRecord.purpose;
            }

            // 3. Populate Date of Screening
            if (currentScreenedRecord.screening_date) {
                document.getElementById('screeningDateInput').value = currentScreenedRecord.screening_date;
            }

            // 4. Select Result and highlight card
            const resultVal = currentScreenedRecord.result || 'Pending';
            const radio = document.querySelector('input[name="result"][value="' + resultVal + '"]');
            if (radio) {
                radio.checked = true;
            }
            highlightResultCard(resultVal);

            // 5. Populate Substances Found
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

            // 7. Show Update Mode Banner
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

            // 8. Update Submit Button Label
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

            const selectEl = document.getElementById('suspectSelect');
            if (selectEl) selectEl.value = '';
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

            renderSuspectPreview();
        }

        // Initialize state on page load
        document.addEventListener('DOMContentLoaded', () => {
            const checkedResult = document.querySelector('input[name="result"]:checked');
            if (checkedResult) {
                highlightResultCard(checkedResult.value);
            } else {
                highlightResultCard('Pending');
            }

            const suspectSelect = document.getElementById('suspectSelect');
            if (suspectSelect && suspectSelect.value) {
                lastSelectedSuspectId = suspectSelect.value;
                renderSuspectPreview();

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

