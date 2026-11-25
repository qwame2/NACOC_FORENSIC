@php
    $photoResolver = app(\App\Services\CasePhotoResolver::class);
    $dDataVault = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    $rawMugCandidate = $case->mugshot_front
        ?? ($case->photo
        ?? ($dDataVault['mugshot_front']
        ?? ($dDataVault['photo']
        ?? ($dDataVault['mugshots']['front']
        ?? ($dDataVault['suspects'][0]['mugshot_front']
        ?? ($dDataVault['suspects'][0]['photo'] ?? null))))));
    $frontPic = $photoResolver->formatMugshotUrl($rawMugCandidate);
    $suspectDisplayName = $case->suspect_name ?: 'Unassigned Target';
    $suspectAlias = $case->suspect_alias ?: 'N/A';
    $suspectDisp = $case->suspect_disposition ?: ($case->status ?? 'Under Investigation');
    $raidLoc = $case->raid_location ?: ($case->seizure_location ?: ($case->holding_facility ?: 'Location Undisclosed'));
    $unitDisplay = $case->unit_name ?: 'Forensic Department';
    $casePriority = strtoupper($case->priority ?: 'HIGH');
    $holdingFacility = $case->holding_facility ?: 'NACOC Central Custody Facility & Vault';
    $loggedAt = $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vault Storage Exhibit Dossier — {{ $case->case_number }} | NACOC Forensic Directorate</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Inter, Cinzel & JetBrains Mono (Matching DPF PDF) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Direct Tailwind script (matching DPF PDF to guarantee all utility classes work) -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            transition: background-color 0.2s ease;
        }
        .font-serif-header {
            font-family: 'Cinzel', 'Times New Roman', Times, serif;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .font-times {
            font-family: 'Times New Roman', Times, Baskerville, Georgia, serif;
        }

        /* DEFAULT: SHOW STANDARD DASHBOARD, HIDE DPF SHEET */
        #dpfPrintableSheet {
            display: none;
        }
        #standardDashboardView {
            display: block;
        }

        /* GUARANTEE FULL BACKGROUND AND COLOR PRINT INTEGRITY */
        *, *::before, *::after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ========================================================
           PRINTABLE VIEW ACTIVE (EXACT DPF PDF DESIGN)
           ======================================================== */
        body.printable-mode {
            background-color: #334155 !important; /* Neutral dark desk background on screen */
            padding: 20px 0 !important;
            min-height: 100vh !important;
        }
        body.printable-mode #standardDashboardView {
            display: none !important;
        }
        body.printable-mode #dpfPrintableSheet {
            display: block !important;
        }

        /* DPF A4 PAGE CONTAINERS: STRICT PHYSICAL A4 SHEET SIZE */
        .a4-page {
            width: 210mm !important;
            height: 297mm !important;
            max-height: 297mm !important;
            min-height: 297mm !important;
            margin: 0 auto 26px auto !important;
            background: #ffffff !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3) !important;
            box-sizing: border-box !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            overflow: hidden !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            color: #0F172A !important;
        }

        .cover-page {
            padding: 12mm 14mm 12mm 14mm !important;
        }

        .manifest-page {
            padding: 10mm 12mm 10mm 12mm !important;
        }

        /* GUARANTEE MAXIMUM TEXT VISIBILITY */
        .a4-page,
        .a4-page h1,
        .a4-page h2,
        .a4-page h3,
        .a4-page h4,
        .a4-page p,
        .a4-page span,
        .a4-page strong,
        .a4-page div {
            color: #0F172A;
        }
        .a4-page .text-dark-bold {
            color: #020617 !important;
            font-weight: 800 !important;
        }
        .a4-page .text-muted-label {
            color: #475569 !important;
            font-weight: 700 !important;
        }
        .a4-page .text-dpf-green {
            color: #047857 !important;
            font-weight: 800 !important;
        }

        /* DPF WATERMARK: GUARANTEED LOW OPACITY BACKGROUND CENTERED ACROSS SCREEN & PRINT */
        .dpf-watermark {
            position: absolute !important;
            inset: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            pointer-events: none !important;
            z-index: 0 !important;
            opacity: 0.05 !important;
            overflow: hidden !important;
            user-select: none !important;
        }
        .dpf-watermark img {
            width: 500px !important;
            height: 500px !important;
            max-width: 500px !important;
            max-height: 500px !important;
            object-fit: contain !important;
        }

        .dpf-page-content {
            position: relative !important;
            z-index: 10 !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }

        @media screen and (max-width: 215mm) {
            .a4-page {
                width: 96vw !important;
                max-width: 96vw !important;
                height: auto !important;
                max-height: none !important;
                padding: 10mm 8mm !important;
            }
        }

        .avoid-break {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        /* ========================================================
           @MEDIA PRINT (DIRECT PHYSICAL PRINT & PDF SAVE)
           EXACT 1:1 REPLICATION OF SCREEN PREVIEW
           ======================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html,
            body,
            body.printable-mode {
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #0F172A !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
                min-height: 0 !important;
                height: auto !important;
                display: block !important;
                overflow: visible !important;
            }

            .no-print,
            header,
            #standardDashboardView,
            #pagePhotoLightboxModal {
                display: none !important;
            }

            #dpfPrintableSheet {
                display: block !important;
                width: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .a4-page {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                box-shadow: none !important;
                box-sizing: border-box !important;
                background: #ffffff !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                overflow: hidden !important;
                position: relative !important;
            }

            .cover-page {
                padding: 12mm 14mm 12mm 14mm !important;
            }

            .manifest-page {
                padding: 10mm 12mm 10mm 12mm !important;
            }

            .a4-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-100">

    <!-- TOP APPLICATION NAVBAR (EXCLUDED FROM PRINT) -->
    <header class="no-print sticky top-0 z-40 bg-slate-900 border-b border-slate-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- LEFT: BACK LINK & BREADCRUMBS -->
            <div class="flex items-center space-x-3 min-w-0">
                <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold transition-all border border-slate-700 shadow-2xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Case Register</span>
                </a>

                <span class="text-slate-600 hidden sm:inline">|</span>

                <div class="flex items-center space-x-2 truncate">
                    <span class="text-[10px] font-mono-code font-extrabold uppercase px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 shrink-0">
                        Vault Storage
                    </span>
                    <span class="text-xs font-mono-code font-extrabold text-blue-400 shrink-0">{{ $case->case_number }}</span>
                    <span class="text-slate-400 text-xs hidden md:inline truncate">&bull; Complete Exhibit Dossier</span>
                </div>
            </div>

            <!-- RIGHT: ACTION CONTROLS -->
            <div class="flex items-center space-x-2.5 shrink-0">
                @if($hasPrelimReport || $isDrugCase)
                    <a href="{{ url('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number) }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-xs transition-all border border-rose-500">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                        <span>Preliminary PDF</span>
                    </a>
                @endif

                <a href="{{ $reviewUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-extrabold rounded-xl shadow-xs transition-all border border-blue-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>Open Case Review</span>
                </a>
            </div>

        </div>
    </header>

    <!-- =========================================================================
         1. INTERACTIVE DASHBOARD VIEW (ACTIVE ON SCREEN IN DEFAULT MODE)
         ========================================================================= -->
    <main id="standardDashboardView" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- SUSPECT PROFILE & CASE HEADER CARD -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-hidden relative">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                
                <!-- SUSPECT AVATAR & METRICS -->
                <div class="flex items-start sm:items-center space-x-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-xs">
                        @if($frontPic)
                            <img src="{{ $frontPic }}" alt="{{ $suspectDisplayName }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center font-black text-slate-500 text-xl font-mono-code bg-slate-200">
                                {{ strtoupper(substr($suspectDisplayName, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="text-xs font-mono-code font-bold uppercase tracking-wider text-blue-600 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-lg">
                                Case File &bull; {{ $case->case_number }}
                            </span>
                            <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-300">
                                {{ $suspectDisp }}
                            </span>
                            <span class="text-[9.5px] font-mono-code font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-300 uppercase">
                                Priority: {{ $casePriority }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ $suspectDisplayName }}
                        </h1>
                        <div class="text-xs text-slate-500 font-mono-code flex items-center gap-2 flex-wrap">
                            <span>Alias / AKA: <strong class="text-slate-800 font-semibold">{{ $suspectAlias }}</strong></span>
                            <span>&bull;</span>
                            <span>Assigned Unit: <strong class="text-blue-700 font-semibold">{{ $unitDisplay }}</strong></span>
                            <span>&bull;</span>
                            <span>Logged: <strong class="text-slate-700 font-semibold">{{ $loggedAt }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT LOCATION & FACILITY PILL -->
                <div class="flex flex-col sm:items-end justify-center space-y-1 bg-slate-50 border border-slate-200 rounded-xl p-3.5 sm:min-w-[240px]">
                    <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Incident / Seizure Location</div>
                    <div class="text-xs font-extrabold text-slate-900 flex items-center gap-1.5 sm:justify-end">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $raidLoc }}</span>
                    </div>
                    <div class="text-[11px] font-mono-code text-slate-500 mt-1">
                        Case Title: <strong class="text-slate-700">{{ $case->title }}</strong>
                    </div>
                </div>

            </div>
        </div>

        <!-- CUSTODIAL METRICS & VAULT LOCATION SUMMARY BANNER -->
        <div class="p-4 bg-gradient-to-r from-amber-50 via-slate-50 to-emerald-50 border border-amber-200/90 rounded-2xl flex flex-wrap items-center justify-between gap-4 text-xs font-mono-code shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-700 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-slate-800 text-sm">
                        Total Exhibits Belonging to Suspect in Vault:
                        <span class="ml-1 font-black px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 text-xs">
                            {{ count($detailedExhibits) }} {{ count($detailedExhibits) === 1 ? 'Item' : 'Items' }}
                        </span>
                    </div>
                    <div class="text-[12.5px] text-slate-500 mt-0.5">
                        Exhibits intake inventory verified under Directorate Vault Custody regulations
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 text-slate-600 flex-wrap">
                <div class="flex items-center gap-1.5 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-2xs">
                    <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-bold text-slate-700">Vault Location:</span>
                    <span class="text-amber-900 font-extrabold">{{ $holdingFacility }}</span>
                </div>
                <div class="flex items-center gap-1.5 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="font-bold text-slate-700">Integrity:</span>
                    <span class="text-emerald-800 font-extrabold uppercase">100% Sealed &amp; Logged</span>
                </div>
            </div>
        </div>

        <!-- COMPLETE EXHIBIT DOSSIERS LIST -->
        <div class="space-y-6">
            @forelse($detailedExhibits as $idx => $ex)
                @php
                    $cat = strtolower($ex['category'] ?? 'General');
                    $badgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
                    if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($cat, 'powder') || str_contains($cat, 'weed') || str_contains($cat, 'cannabis') || str_contains($cat, 'cocaine') || str_contains($cat, 'heroin')) {
                        $badgeClass = 'bg-rose-50 text-rose-800 border-rose-300';
                    } elseif (str_contains($cat, 'weapon') || str_contains($cat, 'firearm') || str_contains($cat, 'ammo') || str_contains($cat, 'gun')) {
                        $badgeClass = 'bg-amber-50 text-amber-900 border-amber-300';
                    } elseif (str_contains($cat, 'vehicle') || str_contains($cat, 'car') || str_contains($cat, 'truck') || str_contains($cat, 'bike') || str_contains($cat, 'motor')) {
                        $badgeClass = 'bg-blue-50 text-blue-900 border-blue-300';
                    } elseif (str_contains($cat, 'digital') || str_contains($cat, 'phone') || str_contains($cat, 'device') || str_contains($cat, 'laptop')) {
                        $badgeClass = 'bg-purple-50 text-purple-900 border-purple-300';
                    } elseif (str_contains($cat, 'cash') || str_contains($cat, 'money') || str_contains($cat, 'currency')) {
                        $badgeClass = 'bg-emerald-50 text-emerald-900 border-emerald-300';
                    }
                    $photos = $ex['photos'] ?? [];
                    $hasLab = !empty($ex['lab']) && is_array($ex['lab']);
                    $hasWeapon = !empty($ex['weapon']) && is_array($ex['weapon']);
                    $hasVehicle = !empty($ex['vehicle']) && is_array($ex['vehicle']);
                    $hasDevice = !empty($ex['device']) && is_array($ex['device']);
                    $exInvOff = !empty($ex['investigating_officer']) && $ex['investigating_officer'] !== 'N/A'
                        ? $ex['investigating_officer']
                        : (!empty($ex['investigation_officer']) && $ex['investigation_officer'] !== 'N/A'
                            ? $ex['investigation_officer']
                            : null);
                    if (empty($exInvOff) && !empty($ex['id'])) {
                        try { $exInvOff = \App\Models\Exhibit::where('id', $ex['id'])->value('investigating_officer'); } catch (\Throwable $e) {}
                    }
                    if (empty($exInvOff) && !empty($ex['exhibit_number'])) {
                        try { $exInvOff = \App\Models\Exhibit::where('exhibit_number', $ex['exhibit_number'])->value('investigating_officer'); } catch (\Throwable $e) {}
                    }
                    if (empty($exInvOff)) {
                        $exInvOff = $case->investigating_officer ?? null;
                    }
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-0 transition-all hover:border-slate-300">
                    
                    <!-- CARD HEADER -->
                    <div class="p-5 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white font-mono-code font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ $idx + 1 }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-slate-900 text-sm sm:text-base">
                                        {{ $ex['commodity_name'] ?: 'Exhibit Item' }}
                                    </span>
                                    <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full border {{ $badgeClass }}">
                                        {{ $ex['category'] ?: 'General' }}
                                    </span>
                                    @if(!empty($ex['exhibit_number']))
                                        <span class="text-[10.5px] font-mono-code font-extrabold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                            Ref: {{ $ex['exhibit_number'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono-code mt-0.5">
                                    Quantity: <strong class="text-slate-800">{{ $ex['quantity_or_weight'] ?? ($ex['quantity'] ?? '1 Unit') }}</strong>
                                    &bull; Value: <strong class="text-emerald-700">{{ $ex['estimated_value'] ?? ($ex['value'] ?? 'N/A') }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 text-[10.5px] font-mono-code font-bold border border-emerald-200 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>Vault Custody Logged</span>
                            </span>
                        </div>
                    </div>

                    <!-- CARD BODY -->
                    <div class="p-6 space-y-6">
                        
                        <!-- INTAKE & SEIZURE METRICS GRID -->
                        <div>
                            <h4 class="text-xs font-mono-code font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>Exhibit Intake &amp; Physical Custody Metrics</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs font-mono-code">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Packaging / Containment</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['packaging'] ?? 'Sealed Evidence Bag' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Concealment Method</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['concealment'] ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Seizure Date &amp; Time</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['seizure_date'] ?? ($ex['date'] ?? 'N/A') }} {{ !empty($ex['seizure_time']) ? ('@ ' . $ex['seizure_time']) : (!empty($ex['time']) ? ('@ ' . $ex['time']) : '') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Seizure Location</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['seizure_location'] ?? ($ex['location'] ?? 'Location Undisclosed') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Seizing Officer</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['seizure_officer'] ?? ($ex['officer'] ?? ($case->submitting_officer ?: 'Assigned Officer')) }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Investigating Officer</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $exInvOff ?: ($ex['investigating_officer'] ?? ($case->submitting_officer ?: 'N/A')) }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Source Requesting Unit</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['requesting_unit'] ?? ($case->unit_name ?: 'Forensic Department') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold">Reason for Seizure</div>
                                    <div class="text-slate-800 font-bold mt-0.5 truncate">{{ $ex['seizure_reason'] ?? ($ex['reason'] ?? 'Suspected Controlled Commodity') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- SPECIALIZED DOMAIN DOSSIERS -->
                        @if($hasLab)
                            <div class="p-4 bg-rose-50/60 rounded-xl border border-rose-200 space-y-2">
                                <div class="text-xs font-mono-code font-extrabold text-rose-900 uppercase flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                    <span>Narcotics &amp; Chemical Laboratory Analysis Dossier</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs font-mono-code pt-1">
                                    <div><span class="text-slate-500">Exam Ref:</span> <strong class="text-rose-950 font-bold">{{ $ex['lab']['exam_reference'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Presumptive Test:</span> <strong class="text-rose-950 font-bold">{{ $ex['lab']['presumptive_result'] ?? 'Positive' }} ({{ $ex['lab']['presumptive_presence'] ?? 'Active' }})</strong></div>
                                    <div><span class="text-slate-500">Purity / Pct:</span> <strong class="text-rose-950 font-bold">{{ $ex['lab']['presumptive_percentage'] ?? 'N/A' }}%</strong></div>
                                    <div><span class="text-slate-500">Confirmatory:</span> <strong class="text-rose-950 font-bold">{{ $ex['lab']['confirmatory_result'] ?? 'Pending' }}</strong></div>
                                    <div><span class="text-slate-500">Sample Description:</span> <strong class="text-slate-800">{{ $ex['lab']['sample_description'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Gross Weight:</span> <strong class="text-slate-800">{{ $ex['lab']['sample_gross'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Sample Taken:</span> <strong class="text-slate-800">{{ $ex['lab']['sample_taken'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Sample Reserved:</span> <strong class="text-slate-800">{{ $ex['lab']['sample_reserved'] ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        @endif

                        @if($hasWeapon)
                            <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 space-y-2">
                                <div class="text-xs font-mono-code font-extrabold text-amber-900 uppercase flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Ballistics &amp; Firearms Technical Specifications</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs font-mono-code pt-1">
                                    <div><span class="text-slate-500">Serial No:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['serial_no'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Make &amp; Model:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['make_model'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Caliber:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['caliber'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Condition:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['condition'] ?? 'Good' }}</strong></div>
                                    <div><span class="text-slate-500">Loaded Status:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['loaded_status'] ?? 'Unloaded' }}</strong></div>
                                    <div><span class="text-slate-500">Ammunition:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['ammunition_details'] ?? 'None' }}</strong></div>
                                    <div><span class="text-slate-500">Marks / Engravings:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['marks'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Location Found:</span> <strong class="text-slate-900 font-bold">{{ $ex['weapon']['location_found'] ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        @endif

                        @if($hasVehicle)
                            <div class="p-4 bg-blue-50/60 rounded-xl border border-blue-200 space-y-2">
                                <div class="text-xs font-mono-code font-extrabold text-blue-900 uppercase flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    <span>Conveyance &amp; Vehicle Specifications Dossier</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs font-mono-code pt-1">
                                    <div><span class="text-slate-500">Make &amp; Model:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['make'] ?? 'N/A' }} {{ $ex['vehicle']['model'] ?? '' }}</strong></div>
                                    <div><span class="text-slate-500">Registration Plate:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['reg_no'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">VIN / Chassis:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['chassis_no'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Engine No:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['engine_no'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Year / Colour:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['year'] ?? 'N/A' }} / {{ $ex['vehicle']['colour'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Mileage:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['mileage'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Registration Country:</span> <strong class="text-slate-900 font-bold">{{ $ex['vehicle']['country'] ?? 'Ghana' }}</strong></div>
                                </div>
                            </div>
                        @endif

                        @if($hasDevice)
                            <div class="p-4 bg-purple-50/60 rounded-xl border border-purple-200 space-y-2">
                                <div class="text-xs font-mono-code font-extrabold text-purple-900 uppercase flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span>Digital Forensics &amp; Electronic Device Specifications</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs font-mono-code pt-1">
                                    <div><span class="text-slate-500">Device Model:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['model'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Serial No:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['serial_no'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">IMEI 1:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['imei1'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">IMEI 2:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['imei2'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">MAC Address:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['mac_address'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Storage Capacity:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['storage'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Device Colour:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['colour'] ?? 'N/A' }}</strong></div>
                                    <div><span class="text-slate-500">Distinguishing Marks:</span> <strong class="text-slate-900 font-bold">{{ $ex['device']['marks'] ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        @endif

                        <!-- DESCRIPTION & REMARKS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono-code">
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                <div class="text-[10px] uppercase font-bold text-slate-500">Description of Exhibits &amp; Intake Manifest</div>
                                <div class="text-slate-800 mt-1 leading-relaxed whitespace-pre-wrap">{{ $ex['description'] ?? ($case->description_of_exhibits ?: 'Standard seized item cataloged under Directorate custody.') }}</div>
                            </div>
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                <div class="text-[10px] uppercase font-bold text-slate-500">Custodian Observations &amp; Chain Path</div>
                                <div class="text-slate-800 mt-1 leading-relaxed whitespace-pre-wrap">{{ $ex['remark'] ?? ($ex['chain_of_custody'] ?? 'Intake logged in vault storage; custody path verified and secure.') }}</div>
                            </div>
                        </div>

                        <!-- PHOTOGRAPHIC EVIDENCE GALLERY -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-mono-code font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Photographic Evidence Gallery</span>
                                </h4>
                                <span class="text-[11px] font-mono-code text-slate-400 font-bold">
                                    {{ count($photos) }} {{ count($photos) === 1 ? 'Photo' : 'Photos' }} Logged
                                </span>
                            </div>

                            @if(count($photos) > 0)
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                    @foreach($photos as $pIdx => $pUrl)
                                        <div class="group relative aspect-square rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shadow-2xs hover:shadow-md transition-all cursor-pointer" onclick="openPagePhotoLightbox('{{ $pUrl }}', 'Exhibit #{{ $idx + 1 }} Photo {{ $pIdx + 1 }}')">
                                            <img src="{{ $pUrl }}" alt="Exhibit Evidence Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy">
                                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-1.5 text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                            </div>
                                            <div class="absolute bottom-1 left-1 bg-slate-900/80 text-white text-[9px] font-mono-code px-1.5 py-0.5 rounded">
                                                #{{ $pIdx + 1 }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-center text-xs font-mono-code text-slate-400 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>No evidence photographs attached to this exhibit</span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            @empty
                <!-- EMPTY EXHIBITS STATE -->
                <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center font-mono-code text-xs text-slate-500 shadow-sm">
                    <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="font-bold text-slate-800 text-base">No exhibits cataloged in Vault Storage</p>
                    <p class="text-[11px] text-slate-400 mt-1 max-w-md mx-auto">There are currently no physical exhibits or contraband parcels cataloged under this suspect's docket in the central vault inventory.</p>
                    <div class="mt-4">
                        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-xs">
                            Return to Case Register
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

    </main>

    <!-- =========================================================================
         2. DPF PDF-STYLED A4 DOCUMENT SHEETS (EXACT DPF_PDF DESIGN SYSTEM)
         ========================================================================= -->
    <div id="dpfPrintableSheet" class="printable-document text-slate-900">

        <!-- DPF TOP TOOLBAR (EXACT MATCHING DPF_PDF) -->
        <div class="max-w-[210mm] mx-auto px-4 sm:px-0 mb-5 no-print">
            <div class="bg-slate-900 text-white p-3.5 rounded-xl flex items-center justify-between shadow-xl border border-slate-700">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 bg-emerald-700 font-bold text-xs tracking-wider rounded uppercase">NACOC VAULT MANIFEST</span>
                    <span class="font-mono-code text-xs text-slate-300 font-bold">Ref: {{ $case->case_number }}</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="togglePrintablePreview()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors border border-slate-700 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Back to Dashboard</span>
                    </button>
                    <button type="button" onclick="handlePrintDocument()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer border border-emerald-500 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print / Save PDF</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================
             PAGE 1: DPF-STYLE OFFICIAL MANIFEST COVER & DOSSIER PAGE
             ============================================================ -->
        <div class="a4-page cover-page relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (GUARANTEED LOW OPACITY BACKGROUND) -->
            <div class="dpf-watermark" aria-hidden="true">
                <img src="{{ asset('nacoc.jfif') }}" onerror="this.onerror=null; this.src='{{ asset('NACOC_LOGO.PNG') }}';" alt="NACOC Watermark" class="w-full h-full object-contain" />
            </div>

            <div class="dpf-page-content">

                <div>
                    <!-- DPF HEADER -->
                    <div class="relative pt-2 pb-5 border-b border-slate-300 text-center">

                        <!-- TOP LEFT BADGE -->
                        <div class="absolute top-0 left-0 text-left">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-950">REPUBLIC OF GHANA</p>
                            <p class="mt-0.5 text-xs font-extrabold text-slate-600 font-mono-code">ISO/IEC 17025</p>
                        </div>

                        <!-- TOP RIGHT BADGE -->
                        <div class="absolute top-0 right-0 text-right">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-950">CONFIDENTIAL</p>
                            <p class="mt-0.5 text-xs font-black uppercase text-slate-700">
                                Page 1 of 2
                            </p>
                        </div>

                        <!-- CENTERED LOGO (MATCHING DPF_PDF) -->
                        <div class="mx-auto w-20 h-20 flex items-center justify-center mb-3">
                            <img src="{{ asset('NACOC_LOGO.PNG') }}" onerror="this.onerror=null; this.src='{{ asset('nacoc.jfif') }}';" alt="NACOC Crest" class="max-h-20 max-w-20 object-contain" />
                        </div>

                        <!-- INSTITUTION DETAILS -->
                        <div>
                            <h1 class="text-xl font-black uppercase tracking-wide text-slate-950 font-serif-header">
                                NARCOTICS CONTROL COMMISSION
                            </h1>
                            <p class="mt-1 text-sm font-black uppercase tracking-[0.14em] text-slate-800">
                                FORENSIC DEPARTMENT
                            </p>
                            <p class="text-xs font-black uppercase tracking-[0.1em] text-slate-700 font-mono-code">
                                EVIDENCE CUSTODY &amp; VAULT STORAGE DIVISION
                            </p>
                        </div>

                    </div>

                    <!-- DPF REPORT TITLE & BANNER -->
                    <div class="mt-5 text-center">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-800">
                            OFFICIAL EVIDENCE CUSTODY &amp; VAULT STORAGE MANIFEST
                        </p>
                        <h2 class="mt-1.5 text-2xl font-black uppercase tracking-wide text-slate-950 font-serif-header">
                            VAULT STORAGE EXHIBIT MANIFEST
                        </h2>
                        <p class="mt-1 text-sm font-bold uppercase tracking-[0.12em] text-slate-700">
                            Physical Custody Inventory &amp; Contraband Intake Log
                        </p>
                        <div class="mx-auto mt-2.5 h-[2.5px] w-16 bg-emerald-700"></div>
                        <p class="mt-3 text-sm font-black uppercase tracking-wider text-emerald-800 font-times">
                            CENTRAL VAULT INVENTORY &bull; {{ strtoupper($holdingFacility) }}
                        </p>
                    </div>

                    <!-- DPF 4-COLUMN REPORT INFORMATION GRID -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.15em] text-emerald-700">
                                    Report Information
                                </p>
                                <p class="text-[12.5px] text-slate-600 font-semibold">
                                    Official Directorate vault registry documentation
                                </p>
                            </div>
                            <p class="text-xs font-black text-slate-700 font-mono-code">
                                Ref: {{ $case->case_number }}
                            </p>
                        </div>

                        <div class="grid grid-cols-4 border-y border-slate-300">
                            <div class="py-2.5 pr-3 border-r border-slate-300">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Case Ref No.</p>
                                <p class="mt-1 text-sm font-bold text-slate-950 font-mono-code break-words">{{ $case->case_number }}</p>
                            </div>
                            <div class="py-2.5 px-3 border-r border-slate-300">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Intake Date</p>
                                <p class="mt-1 text-sm font-bold text-slate-950 font-mono-code break-words">{{ $loggedAt }}</p>
                            </div>
                            <div class="py-2.5 px-3 border-r border-slate-300 min-w-0">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Seizure Location</p>
                                <p class="mt-1 text-sm font-bold text-slate-950 break-words">{{ $raidLoc }}</p>
                            </div>
                            <div class="py-2.5 pl-3">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Holding Vault</p>
                                <p class="mt-1 text-sm font-bold text-slate-950 break-words">{{ $holdingFacility }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 01: SUSPECT & INCIDENT INFORMATION (DPF STYLE) -->
                    <div class="mt-5">
                        <div class="flex items-center gap-3 mb-2 pb-1 border-b border-slate-300">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 border border-emerald-300 shrink-0">
                                01
                            </span>
                            <div class="flex-1 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">
                                    Suspect &amp; Case Information
                                </h3>
                                <span class="text-xs font-black uppercase tracking-wider text-slate-600">
                                    High Court Admissible
                                </span>
                            </div>
                        </div>

                        <div class="flex items-start gap-6 pt-1">
                            <!-- SUSPECT FRONT MUGSHOT (DPF STYLE) -->
                            <div class="shrink-0 flex flex-col items-center">
                                <div class="relative w-28 h-32 rounded-xl overflow-hidden border-2 border-slate-300 bg-slate-100 shadow-xs flex items-center justify-center">
                                    @if($frontPic)
                                        <img src="{{ $frontPic }}" alt="{{ $suspectDisplayName }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="flex flex-col items-center justify-center p-2 text-center text-slate-400">
                                            <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="text-[9px] font-bold uppercase tracking-tight text-slate-500">Front Mugshot</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="mt-1 text-xs font-black uppercase tracking-wider text-emerald-700">
                                    {{ $suspectDisp }}
                                </span>
                            </div>

                            <!-- CASE & SUSPECT DETAILS GRID (DPF STYLE) -->
                            <div class="flex-1 grid grid-cols-2 gap-x-6 gap-y-2.5 pt-0.5 text-sm font-mono-code">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Suspect Name</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-950 break-words">{{ $suspectDisplayName }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Alias / Moniker</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800 break-words">{{ $suspectAlias }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Legal Disposition</p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-900 break-words">{{ $suspectDisp }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Case Priority</p>
                                    <p class="mt-0.5 text-sm font-bold text-blue-700 uppercase break-words">{{ $casePriority }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Submitting Officer</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800 break-words">{{ $case->submitting_officer ?: 'Assigned Officer' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Assigned Unit</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800 break-words">{{ $unitDisplay }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Location of Arrest / Seizure</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800 break-words">{{ $raidLoc }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-600">Case Title</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800 break-words">{{ $case->title }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 02: VAULT CUSTODY & INVENTORY METRICS (DPF STYLE) -->
                    <div class="mt-5">
                        <div class="flex items-center gap-3 mb-2 pb-1 border-b border-slate-300">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 border border-emerald-300 shrink-0">
                                02
                            </span>
                            <div class="flex-1 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">
                                    Vault Custody &amp; Inventory Metrics
                                </h3>
                                <span class="text-[12.5px] font-black uppercase tracking-wider text-slate-600">
                                    Directorate Verified
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 border-y border-slate-300 py-2.5 text-sm font-mono-code">
                            <div class="pr-4 border-r border-slate-300">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Total Exhibits in Vault</p>
                                <p class="mt-1 text-sm font-black text-slate-950 break-words">
                                    {{ count($detailedExhibits) }} {{ count($detailedExhibits) === 1 ? 'Item' : 'Items' }}
                                </p>
                            </div>
                            <div class="px-4 border-r border-slate-300">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Vault Facility Location</p>
                                <p class="mt-1 text-sm font-bold text-slate-950 break-words">{{ $holdingFacility }}</p>
                            </div>
                            <div class="pl-4">
                                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Custodial Integrity</p>
                                <p class="mt-1 text-sm font-extrabold text-emerald-700 uppercase break-words">100% Sealed &amp; Logged</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- DPF PAGE 1 FOOTER -->
                <div class="pt-3 border-t border-slate-300 flex items-center justify-between text-xs font-mono-code font-bold text-slate-600 mt-auto shrink-0">
                    <div class="text-[12.5px]">NACOC FORM FD-VM-01 &bull; FORENSIC DIRECTORATE</div>
                    <div>CONFIDENTIAL &bull; HIGH COURT ADMISSIBLE</div>
                    <div>PAGE 1 OF 2</div>
                </div>

            </div>
        </div>

        <!-- ============================================================
             PAGE 2: DETAILED EXHIBIT MANIFEST & CUSTODIAL ENDORSEMENT
             ============================================================ -->
        <div class="a4-page manifest-page relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="dpf-watermark" aria-hidden="true">
                <img src="{{ asset('nacoc.jfif') }}" onerror="this.onerror=null; this.src='{{ asset('NACOC_LOGO.PNG') }}';" alt="NACOC Watermark" class="w-full h-full object-contain" />
            </div>

            <div class="dpf-page-content">

                <div>
                    <!-- DPF PAGE 2 SUB-HEADER -->
                    <div class="relative pt-1 pb-3 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-black uppercase tracking-wider text-slate-950 font-serif-header">
                                NACOC VAULT MANIFEST &bull; DETAILED EXHIBIT DOCKET
                            </span>
                            <span class="ml-2 font-mono-code text-sm font-black text-slate-800">
                                Ref: {{ $case->case_number }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-600">REPORT</p>
                            <p class="text-sm font-black text-slate-950 font-mono-code">Page 2 of 2</p>
                        </div>
                    </div>

                    <!-- SECTION 03: DETAILED EXHIBIT INVENTORY MANIFEST (DPF STYLE) -->
                    <div class="mt-4">
                        <div class="flex items-center gap-3 mb-2 pb-1 border-b border-slate-300">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 border border-emerald-300 shrink-0">
                                03
                            </span>
                            <div class="flex-1 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">
                                    Detailed Exhibit Inventory Manifest
                                </h3>
                                <span class="text-xs font-black uppercase tracking-wider text-slate-600">
                                    Physical Evidence Register
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse($detailedExhibits as $idx => $ex)
                                @php
                                    $photos = $ex['photos'] ?? [];
                                    $hasLab = !empty($ex['lab']) && is_array($ex['lab']);
                                    $hasWeapon = !empty($ex['weapon']) && is_array($ex['weapon']);
                                    $hasVehicle = !empty($ex['vehicle']) && is_array($ex['vehicle']);
                                    $hasDevice = !empty($ex['device']) && is_array($ex['device']);
                                @endphp
                                <div class="avoid-break pb-2.5 border-b border-slate-300 text-sm font-mono-code">
                                    <!-- ITEM TITLE ROW (DPF ACCENT STYLE) -->
                                    <div class="flex items-center justify-between pb-1 mb-1.5 border-b border-slate-200">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-black text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">
                                                #{{ $idx + 1 }}
                                            </span>
                                            <span class="font-black text-slate-950 text-sm uppercase font-serif-header">
                                                {{ $ex['commodity_name'] ?: 'Exhibit Item' }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-700 border border-slate-300 px-1.5 rounded uppercase">
                                                {{ $ex['category'] ?: 'General' }}
                                            </span>
                                            @if(!empty($ex['exhibit_number']))
                                                <span class="text-xs font-black text-blue-800 font-mono-code">
                                                    Ref: {{ $ex['exhibit_number'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm font-mono-code text-slate-800">
                                            Qty: <strong class="text-slate-950 font-black">{{ $ex['quantity_or_weight'] ?? ($ex['quantity'] ?? '1 Unit') }}</strong>
                                            &bull; Value: <strong class="text-emerald-800 font-black">{{ $ex['estimated_value'] ?? ($ex['value'] ?? 'N/A') }}</strong>
                                        </div>
                                    </div>

                                    <!-- INTAKE CUSTODY METRICS (CLEAN DPF KEY-VALUE GRID) -->
                                    <div class="grid grid-cols-4 gap-x-3 gap-y-1.5 text-xs text-slate-800 py-1.5">
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Packaging:</span> <strong class="text-slate-950 text-sm font-mono-code block break-words">{{ $ex['packaging'] ?? 'Sealed Evidence Bag' }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Concealment:</span> <strong class="text-slate-950 text-sm font-mono-code block break-words">{{ $ex['concealment'] ?? 'N/A' }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Seizure Date:</span> <strong class="text-slate-950 text-sm font-mono-code block break-words">{{ $ex['seizure_date'] ?? ($ex['date'] ?? 'N/A') }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Location:</span> <strong class="text-slate-950 text-sm block break-words">{{ $ex['seizure_location'] ?? ($ex['location'] ?? $raidLoc) }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Seizing Officer:</span> <strong class="text-slate-950 text-sm block break-words">{{ $ex['seizure_officer'] ?? ($ex['officer'] ?? 'Assigned Officer') }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Investigating Officer:</span> <strong class="text-slate-950 text-sm block break-words">{{ $exInvOff ?: ($ex['investigating_officer'] ?? 'N/A') }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Requesting Unit:</span> <strong class="text-slate-950 text-sm block break-words">{{ $ex['requesting_unit'] ?? $unitDisplay }}</strong></div>
                                        <div><span class="text-slate-500 uppercase text-[11px] font-black">Seizure Reason:</span> <strong class="text-slate-950 text-sm block break-words">{{ $ex['seizure_reason'] ?? 'Suspected Controlled Commodity' }}</strong></div>
                                    </div>

                                    <!-- SPECIALIZED DOMAIN SPECIFICATIONS (IF PRESENT) -->
                                    @if($hasLab)
                                        <div class="mt-1 pt-1 border-t border-slate-200 text-xs text-slate-800">
                                            <div class="text-xs font-black text-rose-900 uppercase">Chemical &amp; Lab Analysis:</div>
                                            <div class="grid grid-cols-4 gap-x-2 pt-0.5">
                                                <div>Exam Ref: <strong class="text-sm font-bold">{{ $ex['lab']['exam_reference'] ?? 'N/A' }}</strong></div>
                                                <div>Presumptive: <strong class="text-sm font-bold">{{ $ex['lab']['presumptive_result'] ?? 'Positive' }} ({{ $ex['lab']['presumptive_percentage'] ?? 'N/A' }}%)</strong></div>
                                                <div>Confirmatory: <strong class="text-sm font-bold">{{ $ex['lab']['confirmatory_result'] ?? 'Pending' }}</strong></div>
                                                <div>Gross Weight: <strong class="text-sm font-bold">{{ $ex['lab']['sample_gross'] ?? 'N/A' }}</strong></div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($hasWeapon)
                                        <div class="mt-1 pt-1 border-t border-slate-200 text-xs text-slate-800">
                                            <div class="text-xs font-black text-amber-900 uppercase">Ballistics &amp; Weapon Specs:</div>
                                            <div class="grid grid-cols-4 gap-x-2 pt-0.5">
                                                <div>Serial No: <strong class="text-sm font-bold">{{ $ex['weapon']['serial_no'] ?? 'N/A' }}</strong></div>
                                                <div>Make/Model: <strong class="text-sm font-bold">{{ $ex['weapon']['make_model'] ?? 'N/A' }}</strong></div>
                                                <div>Caliber: <strong class="text-sm font-bold">{{ $ex['weapon']['caliber'] ?? 'N/A' }}</strong></div>
                                                <div>Condition: <strong class="text-sm font-bold">{{ $ex['weapon']['condition'] ?? 'Good' }}</strong></div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($hasVehicle)
                                        <div class="mt-1 pt-1 border-t border-slate-200 text-xs text-slate-800">
                                            <div class="text-xs font-black text-blue-900 uppercase">Conveyance &amp; Vehicle Specs:</div>
                                            <div class="grid grid-cols-4 gap-x-2 pt-0.5">
                                                <div>Make &amp; Model: <strong class="text-sm font-bold">{{ $ex['vehicle']['make'] ?? 'N/A' }} {{ $ex['vehicle']['model'] ?? '' }}</strong></div>
                                                <div>Plate No: <strong class="text-sm font-bold">{{ $ex['vehicle']['reg_no'] ?? 'N/A' }}</strong></div>
                                                <div>Chassis / VIN: <strong class="text-sm font-bold">{{ $ex['vehicle']['chassis_no'] ?? 'N/A' }}</strong></div>
                                                <div>Engine No: <strong class="text-sm font-bold">{{ $ex['vehicle']['engine_no'] ?? 'N/A' }}</strong></div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($hasDevice)
                                        <div class="mt-1 pt-1 border-t border-slate-200 text-xs text-slate-800">
                                            <div class="text-xs font-black text-purple-900 uppercase">Digital Forensics &amp; Device Specs:</div>
                                            <div class="grid grid-cols-4 gap-x-2 pt-0.5">
                                                <div>Model: <strong class="text-sm font-bold">{{ $ex['device']['model'] ?? 'N/A' }}</strong></div>
                                                <div>Serial: <strong class="text-sm font-bold">{{ $ex['device']['serial_no'] ?? 'N/A' }}</strong></div>
                                                <div>IMEI 1: <strong class="text-sm font-bold">{{ $ex['device']['imei1'] ?? 'N/A' }}</strong></div>
                                                <div>Storage: <strong class="text-sm font-bold">{{ $ex['device']['storage'] ?? 'N/A' }}</strong></div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- DESCRIPTION & REMARKS -->
                                    <div class="mt-1.5 text-sm text-slate-800 leading-normal">
                                        <p><strong class="font-black text-slate-950">Description:</strong> {{ $ex['description'] ?? ($case->description_of_exhibits ?: 'Item securely cataloged in central vault storage.') }}</p>
                                        <p class="mt-0.5"><strong class="font-black text-slate-950">Observations:</strong> {{ $ex['remark'] ?? ($ex['chain_of_custody'] ?? 'Intake logged in vault storage; custody path verified and secure.') }}</p>
                                    </div>

                                    <!-- PHOTOS -->
                                    @if(count($photos) > 0)
                                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                                            @foreach($photos as $pIdx => $pUrl)
                                                <div class="w-16 h-16 rounded-lg border border-slate-300 overflow-hidden bg-slate-100 flex items-center justify-center shrink-0">
                                                    <img src="{{ $pUrl }}" alt="Exhibit Evidence Photo" class="w-full h-full object-cover">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="p-6 text-center text-sm font-mono-code text-slate-500">
                                    No physical exhibits cataloged under this docket in vault storage.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- DPF PAGE 2 FOOTER -->
                <div class="pt-3 border-t border-slate-300 flex items-center justify-between text-xs font-mono-code font-bold text-slate-600 mt-auto shrink-0">
                    <div class="text-[12.5px]">NACOC FORM FD-VM-01 &bull; FORENSIC DIRECTORATE</div>
                    <div>CONFIDENTIAL &bull; HIGH COURT ADMISSIBLE</div>
                    <div>PAGE 2 OF 2 &bull; ALL RIGHTS RESERVED</div>
                </div>

            </div>
        </div>

    </div>

    <!-- LIGHTBOX MODAL FOR FULL-SIZE EXHIBIT PHOTOGRAPHIC EVIDENCE (EXCLUDED FROM PRINT) -->
    <div id="pagePhotoLightboxModal" class="hidden no-print fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-3 sm:p-5" onclick="closePagePhotoLightbox(event)">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" onclick="event.stopPropagation()">
            <div class="absolute -top-12 right-0 flex items-center space-x-2">
                <a id="pageLightboxNewTab" href="#" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-mono-code font-bold flex items-center gap-1.5 transition-all text-decoration-none shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Open Original</span>
                </a>
                <button type="button" onclick="closePagePhotoLightbox()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition-all cursor-pointer shadow-xs border border-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <img id="pageLightboxImg" src="" alt="Exhibit Evidence Image" class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl border border-slate-700/80">
            <div id="pageLightboxCaption" class="mt-3 text-xs font-mono-code text-slate-200 font-bold bg-slate-900/90 px-4 py-1.5 rounded-full border border-slate-700/80 shadow-xs">
                Exhibit Evidence Photo
            </div>
        </div>
    </div>

    <script>
        function togglePrintablePreview() {
            const isPrintable = document.body.classList.toggle('printable-mode');
            if (isPrintable) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function handlePrintDocument() {
            if (!document.body.classList.contains('printable-mode')) {
                document.body.classList.add('printable-mode');
            }
            window.print();
        }

        function openPagePhotoLightbox(imgSrc, caption) {
            const modal = document.getElementById('pagePhotoLightboxModal');
            const img = document.getElementById('pageLightboxImg');
            const cap = document.getElementById('pageLightboxCaption');
            const link = document.getElementById('pageLightboxNewTab');
            if (!modal || !img) return;

            img.src = imgSrc;
            if (cap) cap.innerText = caption || 'Exhibit Evidence Photographic Record';
            if (link) link.href = imgSrc;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePagePhotoLightbox(event) {
            if (event && event.target !== event.currentTarget && !event.target.closest('button')) return;
            const modal = document.getElementById('pagePhotoLightboxModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePagePhotoLightbox();
            }
        });
    </script>
</body>
</html>
