<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drug Screening Register | NACOC Forensic Laboratory</title>

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
                <div class="flex items-center gap-4 flex-1 max-w-2xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="text" id="screeningSearchInput" onkeyup="filterScreeningTable(this.value)"
                            placeholder="Search suspect name, alias, Ghana Card, case reference, purpose, or result..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ url('/Lab_Analysis/drug-screening/create') }}"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>New Drug Screening</span>
                    </a>
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
                        {{-- Icon --}}
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>

                        {{-- Body --}}
                        <div class="flex-1 min-w-0 pr-1">
                            <div class="flex items-center gap-1.5 font-mono-code font-bold text-[10.5px] uppercase tracking-wider text-emerald-600">
                                <span>Drug Screening System</span>
                            </div>
                            <div class="text-xs text-slate-800 font-sans mt-1 leading-snug font-semibold">
                                {{ session('success') ?? session('status') }}
                            </div>
                        </div>

                        {{-- Dismiss Button --}}
                        <button type="button" 
                                onclick="dismissTopRightFlashToast()" 
                                class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors p-1.5 rounded-lg shrink-0 cursor-pointer"
                                title="Dismiss notification">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Timeout Progress Bar --}}
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

                <!-- HERO BANNER -->
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 p-6 rounded-3xl text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 bg-teal-500 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">LAB ANALYSIS</span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs font-bold text-teal-400 font-mono-code">SUSPECT SCREENING</span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-white flex items-center gap-3">
                                <span>Drug Screening</span>
                            </h1>
                            <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                Official Forensic Laboratory register displaying all suspect drug screening outcomes, clinical assessment purposes, and laboratory examination findings for arrested persons and suspects in custody.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="text-right">
                                <div class="text-[10px] font-mono-code uppercase text-slate-400">Analysis Officer</div>
                                <div class="text-xs font-bold text-teal-300 font-mono-code">{{ $headName }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STAT CARDS -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Total Screenings -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold tracking-wider">Total Screenings</div>
                            <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono-code">{{ $totalCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">All registered tests</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Positive Cases -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono-code uppercase text-rose-600 font-bold tracking-wider">Positive Screenings</div>
                            <div class="text-2xl font-extrabold text-rose-600 mt-1 font-mono-code">{{ $positiveCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Detected substances</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Negative Cases -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono-code uppercase text-emerald-600 font-bold tracking-wider">Negative Screenings</div>
                            <div class="text-2xl font-extrabold text-emerald-600 mt-1 font-mono-code">{{ $negativeCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Clear / Not detected</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Pending Cases -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono-code uppercase text-amber-600 font-bold tracking-wider">Pending Outcome</div>
                            <div class="text-2xl font-extrabold text-amber-600 mt-1 font-mono-code">{{ $pendingCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Awaiting assay confirmation</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
                    <!-- TABLE HEADER & FILTER TABS -->
                    <div class="px-6 py-4 border-b border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-50/50">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-extrabold uppercase font-mono-code text-slate-700 tracking-wider">Screening Dossiers</span>
                            <span class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full text-[10px] font-mono-code font-bold">{{ $screenings->count() }} records</span>
                        </div>

                        <!-- FILTER BUTTONS -->
                        <div class="flex items-center gap-1.5 p-1 bg-slate-200/70 rounded-xl text-xs font-semibold">
                            <a href="{{ url('/Lab_Analysis/drug-screening') }}"
                                class="px-3 py-1.5 rounded-lg transition-colors {{ !request()->filled('result') ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                All
                            </a>
                            <a href="{{ url('/Lab_Analysis/drug-screening?result=positive') }}"
                                class="px-3 py-1.5 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'positive' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                Positive
                            </a>
                            <a href="{{ url('/Lab_Analysis/drug-screening?result=negative') }}"
                                class="px-3 py-1.5 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'negative' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                Negative
                            </a>
                            <a href="{{ url('/Lab_Analysis/drug-screening?result=pending') }}"
                                class="px-3 py-1.5 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'pending' ? 'bg-amber-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                Pending
                            </a>
                        </div>
                    </div>

                    <!-- TABLE CONTENT -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="drugScreeningTable">
                            <thead>
                                <tr class="bg-slate-100/70 text-[11px] font-extrabold uppercase font-mono-code text-slate-600 border-b border-slate-200">
                                    <th class="py-3 px-4">#</th>
                                    <th class="py-3 px-4">Suspect Details</th>
                                    <th class="py-3 px-4">Case Reference</th>
                                    <th class="py-3 px-4">Purpose of Screening</th>
                                    <th class="py-3 px-4">Screening Date</th>
                                    <th class="py-3 px-4 text-center">Result / Status</th>
                                    <th class="py-3 px-4">Notes / Remarks</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                                @php
                                    $normaliseMugshotUrl = function ($mug) {
                                        if (empty($mug) || !is_string($mug)) {
                                            return null;
                                        }
                                        $mug = trim($mug);
                                        if ($mug === '' || str_starts_with($mug, 'blob:')) {
                                            return null;
                                        }
                                        if (
                                            str_starts_with($mug, 'data:image') ||
                                            str_starts_with($mug, 'http://') ||
                                            str_starts_with($mug, 'https://')
                                        ) {
                                            return $mug;
                                        }
                                        return asset(ltrim($mug, '/'));
                                    };
                                @endphp
                                @forelse($screenings as $index => $screening)
                                @php
                                    $suspect = $screening->suspect;
                                    $parentCase = $suspect ? ($suspect->case ?? ($suspect->singleCase ?? $suspect->multipleCase)) : null;
                                    $resultLower = strtolower(trim($screening->result));

                                    $rawMug = $suspect ? $suspect->mugshot_front : null;
                                    if (empty($rawMug) && $suspect && !empty($suspect->suspect_name)) {
                                        $scMatch = \App\Models\SingleSuspectCase::where('suspect_name', $suspect->suspect_name)
                                            ->whereNotNull('mugshot_front')
                                            ->where('mugshot_front', '!=', '')
                                            ->first();
                                        if ($scMatch && !empty($scMatch->mugshot_front)) {
                                            $rawMug = $scMatch->mugshot_front;
                                        }
                                    }
                                    $mugshotUrl = $normaliseMugshotUrl($rawMug);
                                    $initials = strtoupper(substr(trim($suspect->suspect_name ?? 'SP'), 0, 2));
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors screening-row">
                                    <td class="py-3.5 px-4 font-mono-code font-bold text-slate-500">
                                        {{ str_pad($screening->id, 3, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shadow-2xs shrink-0 bg-slate-100 flex items-center justify-center">
                                                @if($mugshotUrl)
                                                <img src="{{ $mugshotUrl }}" alt="{{ $suspect->suspect_name ?? 'Suspect' }}"
                                                    class="w-full h-full object-cover"
                                                    onerror="this.style.display='none'; if(this.nextElementSibling){ this.nextElementSibling.classList.remove('hidden'); }">
                                                <div class="hidden w-full h-full bg-teal-100 text-teal-700 font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                                    {{ $initials }}
                                                </div>
                                                @else
                                                <div class="w-full h-full bg-teal-100 text-teal-700 font-extrabold text-xs flex items-center justify-center uppercase font-mono-code">
                                                    {{ $initials }}
                                                </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-extrabold text-slate-900 leading-tight">
                                                    {{ $suspect->suspect_name ?? 'Unknown Suspect' }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-medium">
                                                    @if($suspect && $suspect->suspect_alias && $suspect->suspect_alias !== 'N/A')
                                                    <span class="text-teal-700 font-semibold italic">"{{ $suspect->suspect_alias }}"</span> &bull;
                                                    @endif
                                                    <span class="font-mono-code">{{ $suspect->ghana_card ?? 'ID: N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono-code">
                                        <div class="font-bold text-slate-900">
                                            {{ $parentCase->case_number ?? ('NCC-' . date('Y') . '-N/A') }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 truncate max-w-[140px]" title="{{ $parentCase->title ?? '' }}">
                                            {{ $parentCase->title ?? 'General Narcotics Case' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-slate-900 max-w-xs">
                                            {{ $screening->purpose }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono-code font-bold text-slate-700 whitespace-nowrap">
                                        {{ $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        @if($resultLower === 'positive')
                                        <div>
                                            <span class="font-extrabold font-mono-code text-xs text-red-600 tracking-wider">
                                                POSITIVE
                                            </span>
                                            <div class="text-[10px] text-red-600 font-bold max-w-[140px] truncate mx-auto mt-0.5" title="{{ $screening->substances_found ?? 'Substances detected' }}">
                                                {{ $screening->substances_found ?? 'Substances detected' }}
                                            </div>
                                        </div>
                                        @elseif($resultLower === 'negative')
                                        <div>
                                            <span class="font-extrabold font-mono-code text-xs text-emerald-600 tracking-wider">
                                                NEGATIVE
                                            </span>
                                            <div class="text-[10px] text-emerald-600 font-bold font-mono-code mt-0.5">
                                                No drug(s) detected
                                            </div>
                                        </div>
                                        @else
                                        <div>
                                            <span class="font-extrabold font-mono-code text-xs text-amber-600 tracking-wider">
                                                PENDING
                                            </span>
                                            <div class="text-[10px] text-amber-600 font-medium mt-0.5">
                                                Awaiting lab test
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 max-w-xs">
                                        @php
                                            $dispNote = $screening->notes ?? $screening->note ?? null;
                                        @endphp
                                        @if($dispNote)
                                        <div class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed" title="{{ $dispNote }}">
                                            {{ $dispNote }}
                                        </div>
                                        @else
                                        <span class="text-slate-400 italic text-[11px]">No remarks logged</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end space-x-1">
                                            <button type="button"
                                                onclick="openScreeningModal({{ json_encode([
                                                    'id' => str_pad($screening->id, 3, '0', STR_PAD_LEFT),
                                                    'suspect_name' => $suspect->suspect_name ?? 'N/A',
                                                    'suspect_alias' => $suspect->suspect_alias ?? 'N/A',
                                                    'ghana_card' => $suspect->ghana_card ?? 'N/A',
                                                    'gender' => $suspect->gender ?? 'N/A',
                                                    'holding_facility' => $suspect->holding_facility ?? 'NACOC Custody',
                                                    'case_number' => $parentCase->case_number ?? 'N/A',
                                                    'case_title' => $parentCase->title ?? 'N/A',
                                                    'purpose' => $screening->purpose,
                                                    'screening_date' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                                                    'result' => $screening->result,
                                                    'substances_found' => $screening->substances_found ?? ($resultLower === 'negative' ? 'No drug(s) detected' : ($resultLower === 'positive' ? 'Substances detected' : 'Pending laboratory screening')),
                                                    'notes' => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                                                    'note' => $screening->notes ?? $screening->note ?? 'No additional clinical remarks recorded.',
                                                    'created_at' => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
                                                    'mugshot' => $mugshotUrl,
                                                    'initials' => $initials,
                                                ]) }})"
                                                class="p-1.5 text-slate-500 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors cursor-pointer"
                                                title="View Full Screening Record">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>

                                            <form action="{{ url('/Lab_Analysis/drug-screening/' . $screening->id) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this drug screening record for {{ addslashes($suspect->suspect_name ?? 'this suspect') }}?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Delete Record">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                                </svg>
                                            </div>
                                            <div class="text-slate-800 font-extrabold text-sm">No Drug Screening Records Found</div>
                                            <p class="text-xs text-slate-500 max-w-sm">No suspects have had a drug screening recorded matching the active filter.</p>
                                            <a href="{{ url('/Lab_Analysis/drug-screening/create') }}"
                                                class="mt-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-1.5 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <span>Record First Drug Screening</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($screenings, 'links'))
                        <div class="border-t border-slate-200">
                            {{ $screenings->links() }}
                        </div>
                    @endif
                </div>

            </main>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div id="screeningDetailModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-mono-code font-bold uppercase tracking-wider">Screening Dossier #<span id="modalScreeningId">001</span></span>
                </div>
                <button type="button" onclick="closeScreeningModal()" class="text-slate-400 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>
            
            <div class="p-6 space-y-4 text-xs">
                <!-- Suspect Header -->
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center space-x-3">
                    <div id="modalAvatarContainer" class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 shadow-2xs bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center shrink-0 uppercase font-mono-code text-sm">
                        <span id="modalInitials">SP</span>
                    </div>
                    <div>
                        <div id="modalSuspectName" class="font-extrabold text-sm text-slate-900">Suspect Name</div>
                        <div class="text-[11px] text-slate-500 font-mono-code">
                            Case: <span id="modalCaseNumber" class="font-bold text-slate-700">NCC-{{ date('Y') }}</span> &bull; 
                            ID: <span id="modalGhanaCard">GHA-000</span>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Screening Date</div>
                        <div id="modalScreeningDate" class="font-bold text-slate-800 mt-0.5">N/A</div>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Outcome Result</div>
                        <div id="modalResult" class="font-extrabold mt-0.5 font-mono-code">N/A</div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Substance(s) Identified / Found</div>
                    <div id="modalSubstancesFound" class="font-extrabold mt-0.5 font-mono-code text-xs text-slate-800">No drug(s) detected</div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Purpose of Screening</div>
                    <div id="modalPurpose" class="font-semibold text-slate-800 mt-0.5">N/A</div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Notes &amp; Remarks</div>
                    <div id="modalNotes" class="text-slate-700 mt-1 whitespace-pre-wrap leading-relaxed">No remarks.</div>
                </div>

                <div class="text-[10px] text-slate-400 font-mono-code text-right">
                    Logged: <span id="modalCreatedAt">N/A</span>
                </div>
            </div>

            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="button" onclick="closeScreeningModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function filterScreeningTable(term) {
            const query = term.toLowerCase().trim();
            const rows = document.querySelectorAll('.screening-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function openScreeningModal(data) {
            document.getElementById('modalScreeningId').textContent = data.id;
            document.getElementById('modalSuspectName').textContent = data.suspect_name + (data.suspect_alias && data.suspect_alias !== 'N/A' ? ' ("' + data.suspect_alias + '")' : '');
            document.getElementById('modalCaseNumber').textContent = data.case_number;
            document.getElementById('modalGhanaCard').textContent = data.ghana_card;
            document.getElementById('modalScreeningDate').textContent = data.screening_date;
            document.getElementById('modalPurpose').textContent = data.purpose;
            document.getElementById('modalNotes').textContent = data.notes;
            document.getElementById('modalCreatedAt').textContent = data.created_at;

            const avatarContainer = document.getElementById('modalAvatarContainer');
            const initials = data.initials || 'SP';
            if (data.mugshot) {
                avatarContainer.innerHTML = `<img src="${data.mugshot}" alt="${data.suspect_name}" class="w-full h-full object-cover" onerror="this.outerHTML='<span class=\\'font-extrabold font-mono-code text-sm text-teal-800\\'>${initials}</span>'">`;
            } else {
                avatarContainer.innerHTML = `<span class="font-extrabold font-mono-code text-sm text-teal-800">${initials}</span>`;
            }

            const resEl = document.getElementById('modalResult');
            resEl.textContent = data.result.toUpperCase();
            if (data.result.toLowerCase() === 'positive') {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-rose-600';
            } else if (data.result.toLowerCase() === 'negative') {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-emerald-600';
            } else {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-amber-600';
            }

            const subEl = document.getElementById('modalSubstancesFound');
            if (subEl) {
                subEl.textContent = data.substances_found || (data.result.toLowerCase() === 'negative' ? 'No drug(s) detected' : 'None specified');
                if (data.result.toLowerCase() === 'positive') {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-rose-700';
                } else if (data.result.toLowerCase() === 'negative') {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-emerald-700';
                } else {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-amber-700';
                }
            }

            document.getElementById('screeningDetailModal').classList.remove('hidden');
        }

        function closeScreeningModal() {
            document.getElementById('screeningDetailModal').classList.add('hidden');
        }
    </script>
</body>

</html>
