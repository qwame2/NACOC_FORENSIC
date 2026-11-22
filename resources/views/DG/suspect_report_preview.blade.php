<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspect Important Details &amp; Exhibit Report &mdash; NACOC Forensic Department</title>
    
    <!-- TAILWIND CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;0,900;1,400;1,700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #e2e8f0;
            background: #e2e8f0;
            color: #0f172a;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.45;
        }

        .font-serif-header {
            font-family: 'Cinzel', Georgia, serif;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .font-times {
            font-family: 'Merriweather', 'Times New Roman', serif;
        }

        /* A4 Physical Dimensions: Identical in Screen & Print */
        .printable-document {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 0 60px 0;
            background-color: #e2e8f0;
            background: #e2e8f0;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            height: 297mm;
            max-height: 297mm;
            margin: 0 auto 24px auto;
            background: #ffffff !important;
            background-color: #ffffff !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            position: relative;
            box-sizing: border-box;
            padding: 12mm 14mm 10mm 14mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Fixed Cover Page Size */
        .cover-page {
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 14mm 14mm 12mm 14mm;
        }

        .avoid-break-inside {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .suspect-details-row {
            display: flex !important;
            flex-direction: row !important;
            gap: 16px !important;
            align-items: flex-start !important;
        }

        .suspect-biodata-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            column-gap: 24px !important;
            row-gap: 6px !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            html, body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
            }

            .printable-document {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                width: 210mm !important;
            }

            .no-print {
                display: none !important;
            }

            .a4-page {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 12mm 14mm 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                overflow: hidden !important;
                background: #ffffff !important;
            }

            .cover-page {
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 14mm 14mm 12mm 14mm !important;
            }

            .suspect-details-row {
                display: flex !important;
                flex-direction: row !important;
                gap: 16px !important;
                align-items: flex-start !important;
            }

            .suspect-biodata-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                column-gap: 24px !important;
                row-gap: 6px !important;
            }
        }
    </style>
</head>
<body class="antialiased bg-slate-200">

    <!-- TOP ACTION CONTROL BAR (SCREEN VIEW ONLY) -->
    <header class="no-print sticky top-0 z-50 bg-slate-900 border-b border-slate-800 text-white shadow-xl px-4 py-3">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl ?? url('/DG/cases') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg text-xs font-bold font-mono-code transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Case Register</span>
                </a>
                <div class="h-4 w-px bg-slate-700"></div>
                <div>
                    <p class="text-xs text-slate-300 font-mono-code font-bold">
                        {!! strip_tags($dateRangeDisplay) !!}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 bg-slate-800 border border-slate-700 rounded-lg text-xs font-mono-code text-slate-300">
                    <strong class="text-white">{{ count($compiledSuspects) }}</strong> Subject{{ count($compiledSuspects) === 1 ? '' : 's' }}
                </span>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-lg text-xs font-extrabold font-mono-code shadow-md transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- DOCUMENT BODY WRAPPER -->
    <main class="printable-document bg-slate-200">

        <!-- ============================================================
             PAGE 1: CONSOLIDATED EXECUTIVE COVER PAGE
             ============================================================ -->
        <section class="a4-page cover-page relative text-slate-900 select-none bg-white">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-black uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED LAW ENFORCEMENT &amp; FORENSICS</p>
                    </div>
                    <div class="text-right">
                        <p class="font-black uppercase tracking-wider text-slate-900">Confidential</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                            EXECUTIVE REPORT &bull; Page 1 of <span class="doc-total-pages">{{ count($compiledSuspects) + 1 }}</span>
                        </p>
                    </div>
                </div>

                <!-- CENTERED INSTITUTION & REPORT IDENTIFIERS -->
                <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">
                    
                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-xl font-black uppercase tracking-wider text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-700">
                            FORENSIC DEPARTMENT
                        </p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            OFFICE OF THE DIRECTOR-GENERAL
                        </p>
                    </div>

                    <!-- EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-64 h-64 max-w-[280px] max-h-[280px] object-contain drop-shadow-md" />
                    </div>

                    <!-- REPORT TITLE & REGISTER REFERENCE -->
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL SUSPECT &amp; SEIZED PROPERTY REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-black uppercase tracking-wide text-slate-950 font-serif-header">
                            EXECUTIVE CASE REGISTER
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Brief Facts &amp; Custodial Property Inventory
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-blue-700"></div>
                        <p class="mt-5 text-base font-black uppercase tracking-wider text-blue-900">
                            {!! $dateRangeDisplay !!}
                        </p>
                        @if(!empty($isSingleCase) && !empty($caseNumber))
                            <p class="mt-2 text-[13px] font-black uppercase tracking-wider font-times text-slate-900">
                                CASE REFERENCE: {{ $caseNumber }}
                            </p>
                        @endif
                        <p class="mt-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            TOTAL SUBJECTS CATALOGED: {{ count($compiledSuspects) }}
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD -->
                    <div class="mt-10 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
                            <p class="font-black uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This document contains official forensic case intelligence, suspect biodata, Brief Statement of Facts, and custodial chain of custody records compiled for the Director-General. Unauthorized access, copying, or dissemination is strictly prohibited.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="mt-6 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none">
                    <div class="flex items-center gap-2">
                        <span class="font-black text-slate-700 tracking-wider">NACOC / OFFICE OF THE DIRECTOR-GENERAL</span>
                        <span>&bull;</span>
                        <span>Executive Suspect &amp; Exhibit Report</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-black text-slate-800">Page 1 of <span class="doc-total-pages">{{ count($compiledSuspects) + 1 }}</span></span>
                    </div>
                </div>

            </div>
        </section>

        <!-- ============================================================
             PAGE 2+: SUSPECT IMPORTANT DETAILS & EXHIBITS (NO BORDER BG CONTAINERS)
             ============================================================ -->
        @forelse($compiledSuspects as $idx => $item)
            <section class="a4-page relative text-slate-900 bg-white">

                <!-- SUBTLE NACOC WATERMARK -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.05] select-none">
                    <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[700px] h-[700px] object-contain" />
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                    <!-- PAGE HEADER -->
                    <div class="pb-2 border-b border-slate-300 flex items-center justify-between text-[9px] font-mono-code text-slate-700 select-none shrink-0">
                        <div>
                            <span class="font-black uppercase tracking-wider text-slate-900">
                                NACOC FORENSIC DEPARTMENT &bull; EXECUTIVE SUSPECT RECORD
                            </span>
                            <span class="ml-2 font-bold text-slate-800">
                                Ref: {{ $item['case_number'] }} &bull; Priority: {{ $item['priority'] }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-black uppercase tracking-wider text-slate-900">Confidential</span>
                            <span class="ml-1.5 font-black text-slate-800">
                                Page <span class="page-num">{{ $idx + 2 }}</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 1 }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- MAIN CONTENT: NO BORDER BG CONTAINERS -->
                    <div class="flex-1 py-3 space-y-4 overflow-hidden bg-white">

                        <!-- SECTION 1: SUSPECT IMPORTANT DETAILS -->
                        <div class="space-y-2 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Suspect Important Details
                                </h3>
                                <span class="text-xs font-mono-code font-bold text-slate-700 uppercase">
                                    Case: {{ $item['case_number'] }}
                                </span>
                            </div>

                            <div class="suspect-details-row flex flex-row gap-4 items-start">
                                <!-- MUGSHOT PHOTO FRAME -->
                                <div class="shrink-0">
                                    @if(!empty($item['mugshot_front']))
                                        <div class="w-28 h-36 rounded border border-slate-300 overflow-hidden shadow-xs bg-white">
                                            <img src="{{ $item['mugshot_front'] }}" alt="{{ $item['suspect_name'] }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="w-28 h-36 rounded border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 p-2 text-center bg-white">
                                            <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="text-[8.5px] font-mono-code font-bold uppercase tracking-wider">Photo Not Available</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- BIODATA GRID: NO CONTAINER BG, NO CARD BORDER -->
                                <div class="suspect-biodata-grid flex-1 grid grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Legal Full Name</span>
                                        <span class="font-black text-slate-900 text-sm">{{ $item['suspect_name'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Alias / Street Name</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['suspect_alias'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Gender &amp; Age</span>
                                        <span class="font-bold text-slate-800 text-sm">
                                            {{ $item['gender'] }} &bull; {{ $item['age'] ? $item['age'] . ' yrs' : 'N/A' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Nationality</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['nationality'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Ghana Card / National ID</span>
                                        <span class="font-bold font-mono-code text-slate-900 text-sm">{{ $item['ghana_card'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Contact Phone</span>
                                        <span class="font-bold font-mono-code text-slate-800 text-sm">{{ $item['phone'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Custodial Disposition</span>
                                        <span class="font-black text-blue-900 text-sm">{{ $item['disposition'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Secure Custody Facility</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['facility'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Incident / Arrest Date</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['arrest_date'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Offence Scene / Raid Location</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['location'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Lead Submitting Officer</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['submitting_officer'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Assigned Operational Unit</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['unit_name'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: BRIEF STATEMENT OF FACTS (FETCHED DIRECTLY FROM offense_description IN DATABASE) -->
                        <div class="space-y-1.5 pt-1 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Brief Statement of Facts
                                </h3>
                                <span class="text-xs font-mono-code text-slate-600 font-bold uppercase">
                                    Title: {{ $item['case_title'] }}
                                </span>
                            </div>

                            <div class="pt-0.5">
                                <p class="text-slate-800 text-sm text-justify leading-relaxed">
                                    {{ $item['brief_facts'] ?: 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <!-- SECTION 3: ASSOCIATED SEIZED EXHIBITS (WITH NEAT CLEAN TABLE) -->
                        <div class="space-y-2 pt-1 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Associated Seized Exhibits &amp; Custodial Property
                                </h3>
                                <span class="text-xs font-mono-code font-bold text-slate-700">
                                    Total Items: {{ count($item['exhibits']) }}
                                </span>
                            </div>

                            @if(empty($item['exhibits']))
                                <p class="text-sm text-slate-500 italic py-2">
                                    No physical exhibits or seized property logged for this subject in the case register.
                                </p>
                            @else
                                <div class="space-y-3">
                                    @foreach($item['exhibits'] as $exIdx => $ex)
                                        @php
                                            $exNumberDisplay = !empty($ex['exhibit_number']) ? $ex['exhibit_number'] : ('EXH-' . str_pad((string)($exIdx + 1), 5, '0', STR_PAD_LEFT));
                                            
                                            // 1. Fetch commodity_name directly from exhibits table if available
                                            $commodityName = !empty($ex['commodity_name']) && $ex['commodity_name'] !== 'Unnamed Property'
                                                ? $ex['commodity_name']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('commodity_name') ?: (!empty($ex['exhibit_number']) ? \App\Models\Exhibit::where('exhibit_number', $ex['exhibit_number'])->value('commodity_name') : null))
                                                    : (!empty($ex['exhibit_number'])
                                                        ? \App\Models\Exhibit::where('exhibit_number', $ex['exhibit_number'])->value('commodity_name')
                                                        : null));

                                            if (empty($commodityName) && !empty($ex['name']) && $ex['name'] !== 'Unnamed Property') {
                                                $commodityName = $ex['name'];
                                            }
                                            if (empty($commodityName)) {
                                                $commodityName = 'Seized Exhibit';
                                            }

                                            // 2. Quantity / Weight resolution
                                            $qtyWeight = !empty($ex['quantity_or_weight']) && $ex['quantity_or_weight'] !== '1 Unit' && $ex['quantity_or_weight'] !== 'N/A'
                                                ? $ex['quantity_or_weight']
                                                : (!empty($ex['quantity'])
                                                    ? $ex['quantity']
                                                    : (!empty($ex['weight'])
                                                        ? trim($ex['weight'] . ' ' . ($ex['weight_unit'] ?? ''))
                                                        : (!empty($ex['id'])
                                                            ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_quantity') ?: (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_weight') ?: 'N/A'))
                                                            : 'N/A')));

                                            // 3. Estimated Value resolution
                                            $estValue = !empty($ex['estimated_value']) && $ex['estimated_value'] !== 'N/A'
                                                ? $ex['estimated_value']
                                                : (!empty($ex['value']) && $ex['value'] !== 'N/A'
                                                    ? $ex['value']
                                                    : (!empty($ex['id'])
                                                        ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_value') ?: 'N/A')
                                                        : 'N/A'));

                                            // 4. Packaging resolution
                                            $packaging = !empty($ex['packaging']) && $ex['packaging'] !== 'N/A'
                                                ? $ex['packaging']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_packaging') ?: 'Official Sealed Evidence Bag')
                                                    : 'Official Sealed Evidence Bag');

                                            // 5. Concealment Method resolution
                                            $concealment = !empty($ex['concealment']) && $ex['concealment'] !== 'N/A'
                                                ? $ex['concealment']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_concealment') ?: 'N/A')
                                                    : 'N/A');

                                            $vaultFacility = !empty($ex['holding_facility']) ? $ex['holding_facility'] : (!empty($ex['location']) ? $ex['location'] : 'Secure Vault');

                                            $vehSpecs = !empty($ex['vehicle']) ? $ex['vehicle'] : (!empty($ex['vehicle_specs']) ? $ex['vehicle_specs'] : []);
                                            $devSpecs = !empty($ex['device']) ? $ex['device'] : (!empty($ex['electronic_specs']) ? $ex['electronic_specs'] : []);
                                            $wpnSpecs = !empty($ex['weapon']) ? $ex['weapon'] : (!empty($ex['weapon_specs']) ? $ex['weapon_specs'] : []);
                                            $labSpecs = !empty($ex['lab']) ? $ex['lab'] : (!empty($ex['lab_specs']) ? $ex['lab_specs'] : []);
                                        @endphp
                                        <div class="pb-2.5 border-b border-slate-200 last:border-b-0 space-y-1.5 avoid-break-inside">
                                            <div class="flex items-baseline justify-between gap-2 flex-wrap">
                                                <div class="font-black text-slate-900 text-sm">
                                                    <span>Exhibit #{{ $exNumberDisplay }}:</span>
                                                    <span class="text-blue-900">{{ $commodityName }}</span>
                                                    <span class="text-xs text-slate-600 font-normal">({{ $ex['category'] ?? ($ex['exhibit_type'] ?? 'General Exhibit') }})</span>
                                                </div>
                                                <div class="text-xs font-mono-code font-bold text-slate-700">
                                                    Facility: <span class="text-slate-900">{{ $vaultFacility }}</span>
                                                </div>
                                            </div>

                                            <!-- NEAT CLEAN TABLE FOR EXHIBIT SPECIFICATIONS -->
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-300 text-slate-600 font-mono-code uppercase text-[10px] tracking-wider select-none">
                                                            <th class="py-1 px-2 font-bold w-1/4">Quantity / Weight</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Estimated Value</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Packaging</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Concealment Method</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="border-b border-slate-200 text-slate-800 text-xs">
                                                            <td class="py-1.5 px-2 font-bold text-slate-900 font-mono-code">{{ $qtyWeight }}</td>
                                                            <td class="py-1.5 px-2 font-bold text-slate-900 font-mono-code">{{ $estValue }}</td>
                                                            <td class="py-1.5 px-2">{{ $packaging }}</td>
                                                            <td class="py-1.5 px-2">{{ $concealment }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            @php
                                                $fmtSpecVal = function ($v) {
                                                    if (is_array($v)) {
                                                        return implode(', ', array_filter(array_map('strval', $v)));
                                                    }
                                                    return (string) $v;
                                                };
                                            @endphp

                                            <!-- TECHNICAL SPECIFICATIONS IN NEAT CLEAN TABLES -->
                                            @if(!empty($devSpecs))
                                                <div class="overflow-x-auto mt-1">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-blue-900 tracking-wider pb-0.5 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-blue-600 rounded-full inline-block"></span>
                                                        Device Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($devSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($labSpecs))
                                                <div class="overflow-x-auto mt-1">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-emerald-900 tracking-wider pb-0.5 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full inline-block"></span>
                                                        Laboratory Examination Findings
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($labSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($vehSpecs))
                                                <div class="overflow-x-auto mt-1">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-amber-900 tracking-wider pb-0.5 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-amber-600 rounded-full inline-block"></span>
                                                        Vehicle Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($vehSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($wpnSpecs))
                                                <div class="overflow-x-auto mt-1">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-rose-900 tracking-wider pb-0.5 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-rose-600 rounded-full inline-block"></span>
                                                        Weapon Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($wpnSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            <!-- EXHIBIT PHOTOS -->
                                            @if(!empty($ex['photos']) && count($ex['photos']) > 0)
                                                <div class="flex items-center gap-2 pt-1 flex-wrap">
                                                    @foreach($ex['photos'] as $photoUrl)
                                                        <div class="w-14 h-14 rounded border border-slate-300 overflow-hidden bg-white shadow-2xs">
                                                            <img src="{{ $photoUrl }}" alt="Exhibit Photo" class="w-full h-full object-cover">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- DETAILS PAGE FOOTER -->
                    <div class="pt-2 border-t border-slate-300 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-slate-700">NACOC / FORENSIC DEPARTMENT</span>
                            <span>&bull;</span>
                            <span>Case Record: {{ $item['case_number'] }} &bull; Subject: {{ $item['suspect_name'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>STRICTLY CONFIDENTIAL</span>
                            <span>&bull;</span>
                            <span class="font-black text-slate-800">
                                Page <span class="page-num">{{ $idx + 2 }}</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 1 }}</span>
                            </span>
                        </div>
                    </div>

                </div>
            </section>
        @empty
            <section class="a4-page relative text-slate-900 bg-white">
                <div class="flex flex-col items-center justify-center h-full text-center space-y-3 py-16">
                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-base font-black text-slate-800 uppercase">No Suspect Records Found</h3>
                    <p class="text-sm text-slate-500 max-w-md">
                        There are no suspect records or case files matching the specified date range and filters.
                    </p>
                </div>
            </section>
        @endforelse

    </main>

    <!-- DYNAMIC SCRIPT FOR PAGE NUMBERING -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pages = document.querySelectorAll('.a4-page');
            const total = pages.length;
            document.querySelectorAll('.doc-total-pages').forEach(el => {
                el.textContent = total;
            });
            pages.forEach((page, idx) => {
                const pageNumEl = page.querySelector('.page-num');
                if (pageNumEl) {
                    pageNumEl.textContent = idx + 1;
                }
            });
        });
    </script>
</body>
</html>
