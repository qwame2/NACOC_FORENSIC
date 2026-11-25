@php
    $authUser = auth()->user();
    $staffName = $staffName ?? ($authUser->fullname ?? session('user_name') ?? 'Staff Officer');
    $staffParts = preg_split('/\s+/', trim($staffName));
    $staffInitials = count($staffParts) >= 2
        ? strtoupper(substr($staffParts[0], 0, 1) . substr(end($staffParts), 0, 1))
        : strtoupper(substr($staffName, 0, 2));

    $rawCtx = strtolower((string) ($sidebarUnit ?? ''));
    if ($rawCtx === '') {
        if (request()->is('Lab_Analysis*') || request()->is('lab-analysis*')) {
            $rawCtx = 'lab';
        } elseif (request()->is('Digital_Forensics*') || request()->is('digital-forensics*')) {
            $rawCtx = 'digital';
        } elseif (request()->is('Evidence_Management*') || request()->is('evidence-management*')) {
            $rawCtx = 'evidence';
        } elseif (request()->is('Forensic_Head*')) {
            $rawCtx = 'directorate';
        } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) {
            $rawCtx = 'crime';
        } else {
            $rawCtx = strtolower((string) ($authUser->unit ?? 'crime'));
        }
    }

    if (str_contains($rawCtx, 'lab')) {
        $unitKey = 'lab';
        $unitTitle = 'LAB ANALYSIS UNIT';
        $homeUrl = url('/Lab_Analysis/staff/dashboard');
        $caseUrl = url('/Lab_Analysis/staff/case');
        $createUrl = url('/Lab_Analysis/staff/create');
        $createLabel = 'Create New Case';
        $accentBg = 'bg-emerald-600';
        $accentHover = 'hover:bg-emerald-700';
        $accentText = 'text-emerald-600';
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentShadow = 'shadow-emerald-600/25';
    } elseif (str_contains($rawCtx, 'digital') || str_contains($rawCtx, 'physical') || str_contains($rawCtx, 'cyber')) {
        $unitKey = 'digital';
        $unitTitle = 'DIGITAL FORENSICS';
        $homeUrl = url('/Digital_Forensics/staff/dashboard');
        $caseUrl = url('/Digital_Forensics/staff/case');
        $createUrl = url('/Digital_Forensics/staff/create');
        $createLabel = 'Create New Case';
        $accentBg = 'bg-emerald-600';
        $accentHover = 'hover:bg-emerald-700';
        $accentText = 'text-emerald-600';
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentShadow = 'shadow-emerald-600/25';
    } elseif (str_contains($rawCtx, 'evidence') || str_contains($rawCtx, 'vault')) {
        $unitKey = 'evidence';
        $unitTitle = 'EVIDENCE MANAGEMENT';
        $homeUrl = url('/Evidence_Management/staff/dashboard');
        $caseUrl = url('/Evidence_Management/staff/case');
        $createUrl = url('/Evidence_Management/staff/create');
        $createLabel = 'Create New Case';
        $accentBg = 'bg-emerald-600';
        $accentHover = 'hover:bg-emerald-700';
        $accentText = 'text-emerald-600';
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentShadow = 'shadow-emerald-600/25';
    } elseif (str_contains($rawCtx, 'directorate') || str_contains($rawCtx, 'head')) {
        $unitKey = 'directorate';
        $unitTitle = 'FORENSIC DIRECTORATE';
        $homeUrl = url('/Forensic_Head/staff/dashboard');
        $caseUrl = url('/Forensic_Head/staff/case');
        $createUrl = url('/Forensic_Head/staff/create');
        $createLabel = 'Create New Case';
        $accentBg = 'bg-emerald-600';
        $accentHover = 'hover:bg-emerald-700';
        $accentText = 'text-emerald-600';
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentShadow = 'shadow-emerald-600/25';
    } else {
        $unitKey = 'crime';
        $unitTitle = 'CRIME SCENE SUPPORT';
        $homeUrl = url('/Crime_Scene_Support/staff/dashboard');
        $caseUrl = url('/Crime_Scene_Support/staff/case');
        $createUrl = url('/Crime_Scene_Support/staff/create');
        $createLabel = 'Create New Case';
        $accentBg = 'bg-emerald-600';
        $accentHover = 'hover:bg-emerald-700';
        $accentText = 'text-emerald-600';
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentShadow = 'shadow-emerald-600/25';
    }

    $isStaffRollbackActive = request()->is('*/staff/case-rollback*') || request()->is('staff/case-rollback*');
    $isStaffCaseActive = !$isStaffRollbackActive && (request()->is('*/staff/case*') || request()->is('staff/case*'));
    $isStaffDrugScreeningActive = request()->is('*/staff/drug-screening*') || request()->is('*/drug-screening*');
    $isStaffVaultActive = request()->is('*/vault*') || request()->is('vault*') || request()->is('*/exhibit-vault*') || request()->is('exhibit-vault*');
    $isStaffCreateActive = request()->is('*/staff/create*') || request()->is('staff/create*');
    $isStaffReportActive = request()->is('*/staff/report*') || request()->is('staff/report*');
    $isStaffForensicStatusActive = request()->is('*/staff/forensic-status*') || request()->is('*/staff/forensic_status*');
    $isStaffProfileActive = request()->is('profile*') || request()->routeIs('profile*');
    $isStaffHomeActive = request()->is('*/staff/dashboard*') || request()->is('staff/dashboard*') || (!$isStaffCaseActive && !$isStaffRollbackActive && !$isStaffDrugScreeningActive && !$isStaffVaultActive && !$isStaffCreateActive && !$isStaffReportActive && !$isStaffForensicStatusActive && !$isStaffProfileActive && (request()->is('*/staff') || request()->is('staff')));

    $staffRollbackCount = 0;
    if ($authUser) {
        $scopedUnitName = match($unitKey) {
            'lab' => 'Lab Analysis',
            'evidence' => 'Evidence Management',
            'digital' => 'Digital Forensics',
            'directorate' => 'Forensic Directorate',
            default => 'Crime Scene Support',
        };

        try {
            $staffRollbackCount = \App\Models\StaffCaseSubmission::countRollbacksForContext($scopedUnitName, $authUser);
        } catch (\Throwable $e) {
            $staffRollbackCount = 0;
        }
    }
@endphp

<!-- MODERN EXECUTIVE GREY STAFF OFFICERS SIDEBAR -->
<aside class="w-64 bg-slate-50/95 text-slate-800 flex flex-col shrink-0 border-r border-slate-200/90 shadow-xl select-none z-30 relative overflow-x-hidden font-sans">

    <!-- BRAND / SYSTEM HEADER -->
    <div class="px-3.5 pt-4 pb-3 border-b border-slate-200/80 bg-white/70 backdrop-blur-md">
        <div class="flex items-center justify-between gap-2.5 bg-white px-3 py-2.5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all">
            <a href="{{ $homeUrl }}" class="flex items-center space-x-2.5 group min-w-0" title="Go to Dashboard">
                <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200/90 flex items-center justify-center shadow-xs overflow-hidden transition-all group-hover:scale-105 shrink-0">
                    <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <div class="font-extrabold text-slate-900 tracking-tight text-[11px] font-mono-code leading-tight truncate group-hover:text-emerald-700 transition-colors">{{ $unitTitle }}</div>
                    <div class="text-[9.5px] {{ $accentText }} font-extrabold tracking-wider uppercase mt-0.5">STAFF WORKSTATION</div>
                </div>
            </a>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black font-mono-code border {{ $accentBadge }} shrink-0 shadow-2xs">
                STAFF
            </span>
        </div>
    </div>

    <!-- NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">

        <!-- STAFF WORKSPACE -->
        <div>
            <div class="px-3 text-[10px] font-black font-mono-code uppercase text-slate-400 tracking-wider mb-2.5 flex items-center justify-between">
                <span>Staff Workspace</span>
            </div>
            <nav class="space-y-1.5 text-xs font-semibold">
                <a href="{{ $homeUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isStaffHomeActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs' }}">
                    <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ $isStaffHomeActive ? 'text-white' : $accentText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ $caseUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isStaffCaseActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs' }}">
                    <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ $isStaffCaseActive ? 'text-white' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Case Register</span>
                </a>

                @if($unitKey === 'lab')
                @php
                    $canStaffDrugScreening = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('record_lab_analysis', request()->path()) : true);
                    $lockedDrugScreeningTooltip = 'Locked: Lab & Drug Screening permission is disabled by your Head of Unit';
                    $drugScreeningUrl = url('/Lab_Analysis/staff/drug-screening');
                    $drugScreeningEnabledClasses = $isStaffDrugScreeningActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs';
                    $drugScreeningDisabledClasses = 'bg-slate-200/80 text-slate-400 border border-slate-300/70 select-none';
                @endphp
                <a @if($canStaffDrugScreening) href="{{ $drugScreeningUrl }}" @endif
                   data-staff-drug-screening-btn="sidebar"
                   data-drug-screening-url="{{ $drugScreeningUrl }}"
                   aria-disabled="{{ $canStaffDrugScreening ? 'false' : 'true' }}"
                   @if(!$canStaffDrugScreening)
                       tabindex="0"
                       title="{{ $lockedDrugScreeningTooltip }}"
                       data-locked-tooltip="{{ $lockedDrugScreeningTooltip }}"
                       onclick="event.preventDefault(); event.stopPropagation(); return false;"
                       style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                   @endif
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ !$canStaffDrugScreening ? $drugScreeningDisabledClasses : $drugScreeningEnabledClasses }}"
                   data-enabled-class="{{ $drugScreeningEnabledClasses }}"
                   data-disabled-class="{{ $drugScreeningDisabledClasses }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ !$canStaffDrugScreening ? 'text-slate-400' : ($isStaffDrugScreeningActive ? 'text-white' : 'text-teal-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <span class="truncate">Drug Screening</span>
                    </span>
                    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 {{ $canStaffDrugScreening ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </a>
                @endif

                @if($unitKey === 'evidence')
                @php
                    $canStaffManageExhibits = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('manage_exhibits', request()->path()) : true);
                    $lockedVaultTooltip = 'Locked: Manage Exhibit Vault permission is disabled by your Head of Unit';
                    $vaultUrl = url('/Evidence_Management/vault');
                    $vaultEnabledClasses = $isStaffVaultActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs';
                    $vaultDisabledClasses = 'bg-slate-200/80 text-slate-400 border border-slate-300/70 select-none';
                @endphp
                <a @if($canStaffManageExhibits) href="{{ $vaultUrl }}" @endif
                   data-staff-vault-btn="sidebar"
                   data-vault-url="{{ $vaultUrl }}"
                   aria-disabled="{{ $canStaffManageExhibits ? 'false' : 'true' }}"
                   @if(!$canStaffManageExhibits)
                       tabindex="0"
                       title="{{ $lockedVaultTooltip }}"
                       data-locked-tooltip="{{ $lockedVaultTooltip }}"
                       onclick="event.preventDefault(); event.stopPropagation(); return false;"
                       style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                   @endif
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ !$canStaffManageExhibits ? $vaultDisabledClasses : $vaultEnabledClasses }}"
                   data-enabled-class="{{ $vaultEnabledClasses }}"
                   data-disabled-class="{{ $vaultDisabledClasses }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ !$canStaffManageExhibits ? 'text-slate-400' : ($isStaffVaultActive ? 'text-white' : 'text-emerald-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="truncate">Exhibit Vault</span>
                    </span>
                    @php
                        $vaultExhibitsBadge = \App\Models\Exhibit::countPendingQrOrClassification();
                    @endphp
                    @if($vaultExhibitsBadge > 0 && $canStaffManageExhibits)
                        <span id="sidebarStaffExhibitLogBadge" class="ml-auto bg-red-600 text-white border border-red-400 animate-pulse shadow-xs font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold" title="{{ $vaultExhibitsBadge }} exhibit(s) pending QR code or classification">
                            {{ $vaultExhibitsBadge }}
                        </span>
                    @else
                        <span id="sidebarStaffExhibitLogBadge" class="hidden ml-auto font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold" style="display: none !important;"></span>
                    @endif
                    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 {{ $canStaffManageExhibits ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </a>
                @endif

                @if($unitKey === 'digital')
                @php
                    $canStaffGenerateReports = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('generate_reports', request()->path()) : true);
                    $lockedReportsTooltip = 'Locked: Generate Reports & PDFs permission is disabled by your Head of Unit';
                    $forensicStatusUrl = url('/Digital_Forensics/staff/forensic-status');
                    $statusEnabledClasses = $isStaffForensicStatusActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs';
                    $statusDisabledClasses = 'bg-slate-200/80 text-slate-400 border border-slate-300/70 select-none';
                @endphp
                <a @if($canStaffGenerateReports) href="{{ $forensicStatusUrl }}" @endif
                   data-staff-sitrep-btn="sidebar"
                   data-staff-investigation-stat-btn="sidebar"
                   data-sitrep-url="{{ $forensicStatusUrl }}"
                   aria-disabled="{{ $canStaffGenerateReports ? 'false' : 'true' }}"
                   @if(!$canStaffGenerateReports)
                       tabindex="0"
                       title="{{ $lockedReportsTooltip }}"
                       data-locked-tooltip="{{ $lockedReportsTooltip }}"
                       onclick="event.preventDefault(); event.stopPropagation(); return false;"
                       style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                   @endif
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ !$canStaffGenerateReports ? $statusDisabledClasses : $statusEnabledClasses }}"
                   data-enabled-class="{{ $statusEnabledClasses }}"
                   data-disabled-class="{{ $statusDisabledClasses }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ !$canStaffGenerateReports ? 'text-slate-400' : ($isStaffForensicStatusActive ? 'text-white' : 'text-cyan-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="truncate">Investigation Stat &amp; Report</span>
                    </span>
                    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 {{ $canStaffGenerateReports ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </a>
                @endif
            </nav>
        </div>

        <!-- OFFICER ACTIONS -->
        @php
            $canStaffCreateCases = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('create_cases', request()->path()) : true);
            $lockedCreateTooltip = 'Locked: Create New Cases permission is disabled by your Head of Unit';
        @endphp
        <div>
            <div class="px-3 text-[10px] font-black font-mono-code uppercase text-slate-400 tracking-wider mb-2.5 flex items-center justify-between">
                <span>Officer Actions</span>
            </div>
            <nav class="space-y-1.5 text-xs font-semibold">
                <a @if($canStaffCreateCases) href="{{ $createUrl }}" @endif
                   data-staff-create-btn="sidebar"
                   data-create-url="{{ $createUrl }}"
                   aria-disabled="{{ $canStaffCreateCases ? 'false' : 'true' }}"
                   @if(!$canStaffCreateCases)
                       tabindex="0"
                       title="{{ $lockedCreateTooltip }}"
                       data-locked-tooltip="{{ $lockedCreateTooltip }}"
                       onclick="event.preventDefault(); event.stopPropagation(); return false;"
                       style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                   @endif
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ !$canStaffCreateCases ? 'bg-slate-200/80 text-slate-400 border border-slate-300/70 select-none' : ($isStaffCreateActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs') }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ !$canStaffCreateCases ? 'text-slate-400' : ($isStaffCreateActive ? 'text-white' : $accentText) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span class="truncate">{{ $createLabel }}</span>
                    </span>
                    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 {{ $canStaffCreateCases ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </a>

                @php
                    $staffRollbackUrl = match($unitKey) {
                        'lab' => url('/Lab_Analysis/staff/case-rollback'),
                        'evidence' => url('/Evidence_Management/staff/case-rollback'),
                        'digital' => url('/Digital_Forensics/staff/case-rollback'),
                        'directorate' => url('/Forensic_Head/staff/case-rollback'),
                        default => url('/Crime_Scene_Support/staff/case-rollback'),
                    };
                @endphp
                <a href="{{ $staffRollbackUrl }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ $isStaffRollbackActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs' }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ $isStaffRollbackActive ? 'text-white' : 'text-rose-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span>Case Rollback</span>
                    </span>
                    <span id="sidebarStaffRollbackBadge"
                          data-rollback-badge="sidebar"
                          class="{{ $staffRollbackCount > 0 ? 'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-black font-mono-code text-white bg-red-600 rounded-full animate-pulse shadow-xs shrink-0 ml-2' : 'hidden' }}"
                          style="{{ $staffRollbackCount > 0 ? '' : 'display: none;' }}"
                          title="{{ $staffRollbackCount > 0 ? $staffRollbackCount . ' Case(s) awaiting corrections' : '' }}">
                        {{ $staffRollbackCount > 0 ? $staffRollbackCount : '' }}
                    </span>
                </a>

                @if($unitKey !== 'digital')
                @php
                    $staffReportUrl = match($unitKey) {
                        'lab' => url('/Lab_Analysis/staff/report'),
                        'evidence' => url('/Evidence_Management/staff/report'),
                        'directorate' => url('/Forensic_Head/staff/report'),
                        default => url('/Crime_Scene_Support/staff/report'),
                    };
                    $isStaffReportActive = request()->is('*/staff/report*') || request()->is('staff/report*');
                    $canStaffGenerateReports = !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('generate_reports', request()->path()) : true);
                    $lockedReportsTooltip = 'Locked: Generate Reports & PDFs permission is disabled by your Head of Unit';
                    $reportEnabledClasses = $isStaffReportActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs';
                    $reportDisabledClasses = 'bg-slate-200/80 text-slate-400 border border-slate-300/70 select-none';
                @endphp
                <a @if($canStaffGenerateReports) href="{{ $staffReportUrl }}" @endif
                   data-staff-sitrep-btn="sidebar"
                   data-sitrep-url="{{ $staffReportUrl }}"
                   aria-disabled="{{ $canStaffGenerateReports ? 'false' : 'true' }}"
                   @if(!$canStaffGenerateReports)
                       tabindex="0"
                       title="{{ $lockedReportsTooltip }}"
                       data-locked-tooltip="{{ $lockedReportsTooltip }}"
                       onclick="event.preventDefault(); event.stopPropagation(); return false;"
                       style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                   @endif
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ !$canStaffGenerateReports ? $reportDisabledClasses : $reportEnabledClasses }}"
                   data-enabled-class="{{ $reportEnabledClasses }}"
                   data-disabled-class="{{ $reportDisabledClasses }}">
                    <span class="flex items-center min-w-0">
                        <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ !$canStaffGenerateReports ? 'text-slate-400' : ($isStaffReportActive ? 'text-white' : 'text-indigo-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate">Unit Sitrep &amp; Reports</span>
                    </span>
                    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 {{ $canStaffGenerateReports ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </a>
                @endif

                <a href="{{ url('/profile') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isStaffProfileActive ? 'text-white ' . $accentBg . ' shadow-md ' . $accentShadow . ' font-bold' : 'text-slate-700 hover:text-slate-950 hover:bg-white hover:shadow-2xs' }}">
                    <svg class="w-4.5 h-4.5 mr-3 shrink-0 {{ $isStaffProfileActive ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>My Profile</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- STAFF OFFICER PROFILE CARD -->
    @php
        $userAvatar = $authUser->avatar ?? null;
    @endphp
    <div class="p-3 bg-white/80 border-t border-slate-200/80 backdrop-blur-xs mt-auto">
        <div class="flex items-center justify-between gap-2.5 bg-slate-50/90 hover:bg-slate-100/90 px-3 py-2.5 rounded-2xl border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all">
            <a href="{{ url('/profile') }}" class="flex items-center space-x-2.5 min-w-0 flex-1 group" title="View Profile ({{ $staffName }})">
                @if(!empty($userAvatar))
                    <img src="{{ $userAvatar }}" alt="{{ $staffName }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shadow-2xs shrink-0 group-hover:scale-105 transition-transform">
                @else
                    <div class="w-9 h-9 rounded-xl {{ $accentBg }} flex items-center justify-center font-extrabold text-xs text-white shadow-xs {{ $accentShadow }} shrink-0 group-hover:scale-105 transition-transform">
                        {{ $staffInitials }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-black text-slate-900 truncate group-hover:text-emerald-700 transition-colors tracking-tight" title="{{ $staffName }}">{{ $staffName }}</div>
                </div>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" title="Sign Out" class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>

    @include('partials.sidebar_badge_poller')
</aside>
