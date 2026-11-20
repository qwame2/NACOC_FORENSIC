@php
    $printAuthUser = auth()->user();
    
    // Resolve NACOC Logo as base64 data URI
    $logoPngPath = public_path('NACOC_LOGO.PNG');
    $logoPngLower = public_path('NACOC_LOGO.png');
    $resolvedLogo = file_exists($logoPngPath) ? $logoPngPath : (file_exists($logoPngLower) ? $logoPngLower : null);
    $printLogoSrc = $resolvedLogo ? ('data:image/png;base64,' . base64_encode(file_get_contents($resolvedLogo))) : url('NACOC_LOGO.PNG', [], false);

    // Resolve NACOC Watermark as base64 data URI
    $watermarkJfif = public_path('nacoc.jfif');
    $watermarkSrc = file_exists($watermarkJfif) ? ('data:image/jpeg;base64,' . base64_encode(file_get_contents($watermarkJfif))) : url('nacoc.jfif', [], false);

    $printUnitLabel = $unitLabel ?? ($unitContext['label'] ?? ($sidebarUnit ?? 'Forensic Operations Unit'));
    $printGeneratedAt = now()->format('d M Y \a\t H:i:s');
    $printGeneratedBy = ($printAuthUser?->fullname) ? ($printAuthUser->fullname . ' (' . ($printAuthUser->service_id ?? 'Unit Head') . ')') : 'Authorized Forensic Unit Head';
    
    $printTotalLogs = number_format($stats['total'] ?? (isset($logs) ? $logs->total() : 0));
    $printUniqueStaff = number_format($stats['unique_staff'] ?? 0);
    $printMutations = number_format($stats['mutations'] ?? 0);
    $printTodayLogs = number_format($stats['today'] ?? 0);
    $printFilteredLogs = isset($logs) ? number_format($logs->total()) : $printTotalLogs;
    $printIsFiltered = (!empty($search) || (!empty($actionFilter) && $actionFilter !== 'ALL') || (!empty($dateRange) && $dateRange !== 'all') || !empty($effectiveUnit));

    $rangeNames = [
        'today'     => "Today's Operations (" . date('d M Y') . ")",
        'yesterday' => "Yesterday's Operations (" . date('d M Y', strtotime('-1 day')) . ")",
        '7days'     => "Past 7 Days Audit Record",
        '30days'    => "Past 30 Days Audit Record",
        'month'     => "Current Month (" . date('F Y') . ")",
        'all'       => "Complete Operational Ledger (All Logged Sessions)",
    ];
    $printScopeLabel = $rangeNames[$dateRange ?? 'all'] ?? 'Complete Operational Ledger';
    if (!empty($search)) {
        $printScopeLabel .= ' • Query: "' . e($search) . '"';
    }
    if (!empty($actionFilter) && strtoupper($actionFilter) !== 'ALL') {
        $printScopeLabel .= ' • Action: ' . e($actionFilter);
    }
@endphp

<!-- ============================================================
     PAGE 1: OFFICIAL FORENSIC STAFF AUDIT COVER PAGE
     (IDENTICAL TO NACOC EXECUTIVE REPORTS)
     ============================================================ -->
<div class="a4-page cover-page page-break relative text-slate-900 select-none print-header-container">

    <!-- SUBTLE NACOC WATERMARK -->
    <div class="cover-watermark">
        <img src="{{ $watermarkSrc }}" alt="NACOC Watermark" style="width: 550px; height: 550px; object-fit: contain;" />
    </div>

    <div class="cover-content">

        <!-- TOP BADGES -->
        <div class="pt-1 flex items-center justify-between text-[8.5pt] font-mono-code select-none">
            <div class="text-left">
                <p class="font-black uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                <p class="text-[7.5pt] text-slate-600 mt-0.5">ISO/IEC 17025 COMPLIANT AUDIT TRAIL</p>
            </div>
            <div class="text-right">
                <p class="font-black uppercase tracking-wider text-rose-800">CONFIDENTIAL // RESTRICTED</p>
                <p class="mt-0.5 text-[8.5pt] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                    STAFF AUDIT DOSSIER &bull; COVER PAGE (PAGE 1)
                </p>
            </div>
        </div>

        <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE -->
        <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">

            <!-- INSTITUTION DETAILS -->
            <div>
                <h1 class="text-2xl font-black uppercase tracking-wider text-slate-950 font-cinzel leading-tight">
                    NARCOTICS CONTROL COMMISSION
                </h1>
                <p class="mt-1 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-700">
                    FORENSIC SERVICES DIRECTORATE
                </p>
                <p class="mt-1 text-[11pt] font-extrabold uppercase tracking-[0.12em] text-emerald-800 font-mono-code">
                    {{ $printUnitLabel }}
                </p>
            </div>

            <!-- EMBLEM LOGO IN THE MIDDLE (LARGE MATCHING OTHER REPORTS) -->
            <div class="my-6 flex items-center justify-center">
                <img src="{{ $printLogoSrc }}" alt="NACOC Logo" style="width: 190px; height: 190px; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.12));" />
            </div>

            <!-- REGISTER & REPORT TITLE -->
            <div>
                <p class="text-[9pt] font-bold uppercase tracking-[0.25em] text-slate-900">
                    OFFICIAL SYSTEM INTEGRITY &amp; OVERSIGHT AUDIT
                </p>
                <h2 class="mt-2 text-2xl font-black uppercase tracking-wide text-slate-950 font-cinzel">
                    STAFF AUDIT &amp; ACTIVITY REGISTER
                </h2>
                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">
                    Immutable Forensic Operational Activity &amp; Transaction Ledger
                </p>
                <div style="height: 2.5px; width: 90px; background-color: #047857; margin: 12px auto;"></div>
                <p class="mt-3 text-sm font-black uppercase tracking-wider text-emerald-800 font-mono-code">
                    {{ $printUnitLabel }}
                </p>
                <p class="mt-1 text-[8.5pt] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                    {{ $printScopeLabel }}
                </p>
            </div>

            <!-- STAFF AUDIT INFORMATION GRID (4 CRISP METRIC CARDS) -->
            <div class="mt-6 w-full grid grid-cols-4 gap-3 text-center border-t border-b border-slate-300 py-3 font-mono-code">
                <div class="p-2.5 bg-slate-50/90 rounded-lg border border-slate-200">
                    <span class="text-slate-500 uppercase block text-[7pt] font-bold">Total Logged Records</span>
                    <span class="font-black text-slate-950 text-[13pt] block mt-0.5 tnum">{{ $printTotalLogs }}</span>
                    <span class="text-[6.5pt] text-slate-500 block">System Entries</span>
                </div>
                <div class="p-2.5 bg-slate-50/90 rounded-lg border border-slate-200">
                    <span class="text-slate-500 uppercase block text-[7pt] font-bold">Monitored Staff</span>
                    <span class="font-black text-slate-950 text-[13pt] block mt-0.5 tnum">{{ $printUniqueStaff }}</span>
                    <span class="text-[6.5pt] text-slate-500 block">Active Officers</span>
                </div>
                <div class="p-2.5 bg-slate-50/90 rounded-lg border border-slate-200">
                    <span class="text-slate-500 uppercase block text-[7pt] font-bold">Mutations / Writes</span>
                    <span class="font-black text-emerald-800 text-[13pt] block mt-0.5 tnum">{{ $printMutations }}</span>
                    <span class="text-[6.5pt] text-slate-500 block">Database Changes</span>
                </div>
                <div class="p-2.5 bg-slate-50/90 rounded-lg border border-slate-200">
                    <span class="text-slate-500 uppercase block text-[7pt] font-bold">Classification</span>
                    <span class="font-black text-rose-800 text-[11pt] block mt-1 tracking-tight">RESTRICTED</span>
                    <span class="text-[6.5pt] text-slate-500 block">Law Enforcement</span>
                </div>
            </div>

            <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
            <div class="mt-5 w-full">
                <div class="p-3.5 text-center font-mono-code text-[8pt] text-slate-700 space-y-1 border border-slate-200 bg-slate-50/50 rounded-xl">
                    <p class="font-black uppercase tracking-wider text-slate-900 text-[8.5pt]">
                        CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                    </p>
                    <p class="leading-relaxed text-[7.5pt]">
                        This official staff audit register contains immutable digital records of authorized forensic personnel actions, evidence movements, and chain-of-custody transactions pursuant to the Narcotics Control Commission Act, 2020 (Act 1019). Unauthorized reproduction, alteration, or external distribution is strictly prohibited and subject to statutory prosecution.
                    </p>
                </div>
            </div>

            <!-- SIGN-OFF AUTHENTICATION ACCREDITATION PREVIEW -->
            <div class="mt-5 w-full grid grid-cols-2 gap-8 text-left border-t border-slate-200 pt-3">
                <div class="text-[8pt] font-mono-code">
                    <span class="text-slate-500 uppercase tracking-wider block text-[7pt] font-bold">COMPILING UNIT:</span>
                    <span class="font-black text-slate-900 text-[8.5pt] block">{{ $printUnitLabel }}</span>
                    <span class="block text-slate-600 text-[7.5pt]">NACOC Forensic Directorate, Headquarters, Accra</span>
                </div>
                <div class="text-right text-[8pt] font-mono-code">
                    <span class="text-slate-500 uppercase tracking-wider block text-[7pt] font-bold">OVERSIGHT AUTHORITY:</span>
                    <span class="font-black text-slate-900 text-[8.5pt] block truncate">{{ $printGeneratedBy }}</span>
                    <span class="block text-slate-600 text-[7.5pt]">Authorized Forensic Unit Head / Directorate Lead</span>
                </div>
            </div>

        </div>

        <!-- COVER PAGE FOOTER -->
        <div class="mt-4 pt-2.5 border-t border-slate-300 flex items-center justify-between text-[7.5pt] text-slate-500 font-mono-code select-none">
            <div class="flex items-center gap-2">
                <span class="font-black text-slate-800 tracking-wider">NACOC / FORENSIC SERVICES DIRECTORATE</span>
                <span>&bull;</span>
                <span>Official Staff Audit Register</span>
            </div>
            <div class="flex items-center gap-2">
                <span>STRICTLY CONFIDENTIAL</span>
                <span>&bull;</span>
                <span class="font-bold text-slate-800">Cover Page (Page 1)</span>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     CONTINUATION RUNNING HEADER FOR BODY (PAGES 2+)
     ============================================================ -->
<div class="print-continuation-header">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 shrink-0 flex items-center justify-center p-0.5 bg-white border border-slate-300 rounded-lg">
                <img src="{{ $printLogoSrc }}" alt="NACOC Logo" style="max-height: 36px; max-width: 36px; object-fit: contain;" />
            </div>
            <div>
                <div class="text-[7.5pt] font-mono-code font-black uppercase tracking-wider text-emerald-800 leading-tight">
                    Republic of Ghana &bull; Narcotics Control Commission
                </div>
                <div class="text-[10pt] font-black uppercase tracking-tight text-slate-950 font-cinzel leading-tight">
                    Staff Activity &amp; Audit Trail &bull; {{ $printUnitLabel }}
                </div>
            </div>
        </div>
        <div class="text-right font-mono-code text-[7pt]">
            <div class="font-black text-slate-900 uppercase">Ref ID: NACOC-AUD-{{ date('Ymd-His') }}</div>
            <div class="text-slate-600 font-medium">Printed: {{ $printGeneratedAt }} &bull; <span class="font-black text-rose-800">RESTRICTED</span></div>
        </div>
    </div>
</div>
