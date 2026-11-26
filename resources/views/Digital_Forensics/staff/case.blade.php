@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst';
    $staffName = $authUser->fullname ?? session('user_name') ?? 'Cyber Forensic Specialist';

    $staffParts = preg_split('/\s+/', trim($staffName));
    $staffInitials = count($staffParts) >= 2 
        ? strtoupper(substr($staffParts[0], 0, 1) . substr(end($staffParts), 0, 1))
        : strtoupper(substr($staffName, 0, 2));

    $sidebarUnit = 'Digital Forensics';
    $allCases = $allCases ?? \App\Models\ForensicCase::allCases();
    $digitalCases = $allCases;

    if (!isset($cases) || !($cases instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
        $cases = \App\Models\ForensicCase::paginateCases($digitalCases, (int) request()->input('per_page', 15));
    }

    $totalDigitalCount = count($digitalCases);
    $triageCount = $digitalCases->filter(fn($c) => in_array(strtolower($c->status ?? ''), ['triage', 'pending', 'pending extraction', 'submitted']))->count();
    $extractionCount = $digitalCases->filter(fn($c) => in_array(strtolower($c->status ?? ''), ['in progress', 'extraction ongoing', 'under analysis', 'imaging']))->count();
    $completedCount = $digitalCases->filter(fn($c) => in_array(strtolower($c->status ?? ''), ['completed', 'approved', 'closed', 'extracted']))->count();

    $pageCaseNumbers = collect($cases->items())->pluck('case_number')->filter()->values();
    $pageCreatorIds = collect($cases->items())->pluck('user_id')->filter()->unique()->values();
    $creatorHeadMap = $pageCreatorIds->isNotEmpty()
        ? \App\Models\User::whereIn('id', $pageCreatorIds)->pluck('is_head', 'id')
        : collect();
    $hasSubmissionsTable = \Illuminate\Support\Facades\Schema::hasTable('staff_case_submissions');
    $approvedSubmissionNumbers = ($hasSubmissionsTable && $pageCaseNumbers->isNotEmpty())
        ? \App\Models\StaffCaseSubmission::whereIn('approved_case_number', $pageCaseNumbers)
            ->where('status', 'Approved')
            ->pluck('approved_case_number')
            ->flip()
        : collect();
    $pendingStaffSubmissions = ($hasSubmissionsTable && (!method_exists($cases, 'currentPage') || $cases->currentPage() === 1))
        ? \App\Models\StaffCaseSubmission::forUnit('Digital & Physical Forensics')
            ->where('status', 'Pending Approval')
            ->orderByDesc('created_at')
            ->get()
        : collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Digital Forensics Staff Case Register | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif
    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-slate-50 text-slate-900 selection:bg-purple-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        {{-- STANDALONE STAFF SIDEBAR --}}
        @include('staff.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- MODERN FLOATING TOPBAR WITH BACKSHADOW & BORDER-RADIUS -->
            <header class="sticky top-2.5 z-40 mx-4 mt-2 mb-1 px-5 py-3 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/90 shadow-[0_10px_25px_-5px_rgba(15,23,42,0.08),0_4px_10px_-2px_rgba(15,23,42,0.04)] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 flex-1 max-w-xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" id="globalSearch" onkeyup="filterStaffCaseRows()" placeholder="Search Device Serial, IMEI, Make, Hash, Suspect..." class="w-full pl-10 pr-12 py-2 bg-slate-100/80 border border-slate-200/90 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9px] font-mono-code font-bold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-mono-code font-bold bg-purple-50 text-purple-800 border border-purple-200/80 shadow-2xs">
                        DFPU Staff Active
                    </span>

                    @php
                        $canHeaderCreateCases = !auth()->user() || auth()->user()->is_head || (method_exists(auth()->user(), 'hasPermission') ? auth()->user()->hasPermission('create_cases', 'Digital & Physical Forensics') : true);
                        $headerLockedTooltip = 'Locked: Create New Cases permission is disabled by your Head of Unit';
                    @endphp
                    <a @if($canHeaderCreateCases) href="{{ url('/Digital_Forensics/staff/create') }}" @endif
                       data-staff-create-btn="header"
                       data-create-url="{{ url('/Digital_Forensics/staff/create') }}"
                       aria-disabled="{{ $canHeaderCreateCases ? 'false' : 'true' }}"
                       @if(!$canHeaderCreateCases)
                           tabindex="0"
                           title="{{ $headerLockedTooltip }}"
                           data-locked-tooltip="{{ $headerLockedTooltip }}"
                           onclick="event.preventDefault(); event.stopPropagation(); return false;"
                           style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                       @endif
                       class="inline-flex items-center gap-1.5 px-4 py-2 {{ $canHeaderCreateCases ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/25 hover:scale-[1.02]' : 'bg-slate-200 text-slate-400 border border-slate-300 select-none' }} font-extrabold text-xs rounded-xl transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Create New Case</span>
                        <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 {{ $canHeaderCreateCases ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </a>
                </div>
            </header>

            <!-- MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- HERO BANNER -->
                <div class="relative overflow-hidden rounded-2xl p-6 bg-white border border-slate-200/90 shadow-md shadow-slate-200/50">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-2 mb-1.5">
                                <span class="px-2.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider shadow-2xs">
                                    DIGITAL &amp; PHYSICAL FORENSICS (DPFU)
                                </span>
                                <span class="text-xs text-slate-300">|</span>
                                <span class="text-xs font-bold text-slate-600 font-mono-code">STAFF CASE DIRECTORY</span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Active Incident &amp; Case Registry</h1>
                            <p class="text-xs text-slate-500 mt-1">Analyst: <strong class="text-slate-800">{{ $staffName }}</strong> <span class="text-slate-300 mx-1">|</span> Unit Head: <strong>{{ $headName }}</strong></p>
                        </div>

                        @php
                            $canStaffGenerateReports = !auth()->user() || auth()->user()->is_head || (method_exists(auth()->user(), 'hasPermission') ? auth()->user()->hasPermission('generate_reports', request()->path()) : true);
                            $lockedReportsTooltip = 'Locked: Generate Reports & PDFs permission is disabled by your Head of Unit';
                            $forensicStatusUrl = url('/Digital_Forensics/staff/forensic-status');
                        @endphp
                        <div class="flex items-center gap-2">
                            <a @if($canStaffGenerateReports) href="{{ $forensicStatusUrl }}" @endif
                               data-staff-sitrep-btn="header"
                               data-staff-investigation-stat-btn="header"
                               data-sitrep-url="{{ $forensicStatusUrl }}"
                               aria-disabled="{{ $canStaffGenerateReports ? 'false' : 'true' }}"
                               @if(!$canStaffGenerateReports)
                                   tabindex="0"
                                   title="{{ $lockedReportsTooltip }}"
                                   data-locked-tooltip="{{ $lockedReportsTooltip }}"
                                   onclick="event.preventDefault(); event.stopPropagation(); return false;"
                                   style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
                               @endif
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 border text-xs font-bold rounded-xl transition-all shadow-2xs {{ !$canStaffGenerateReports ? 'bg-slate-200/80 text-slate-400 border-slate-300/70 select-none' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' }}"
                               data-enabled-class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200"
                               data-disabled-class="bg-slate-200/80 text-slate-400 border-slate-300/70 select-none">
                                <span>Investigation Stats</span>
                                <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 {{ $canStaffGenerateReports ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPI STAT CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-4.5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-500 tracking-wider">Total Device Cases</div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code mt-1">{{ $totalDigitalCount }}</div>
                            <div class="text-[11px] font-semibold text-purple-600 mt-0.5">Assigned to DPFU</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white p-4.5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-500 tracking-wider">Acquisition / Imaging</div>
                            <div class="text-2xl font-black text-amber-600 font-mono-code mt-1">{{ $extractionCount }}</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Physical / Bitstream DD</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                    </div>

                    <div class="bg-white p-4.5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-500 tracking-wider">Device Triage</div>
                            <div class="text-2xl font-black text-blue-600 font-mono-code mt-1">{{ $triageCount }}</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Hardware Inspection</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                        </div>
                    </div>

                    <div class="bg-white p-4.5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-500 tracking-wider">Completed Extractions</div>
                            <div class="text-2xl font-black text-emerald-600 font-mono-code mt-1">{{ $completedCount }}</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Dossiers Verified</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- FILTER CONTROLS & TABLE CARD -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden space-y-4 p-5">
                    
                    <!-- FILTER CONTROLS BAR -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider font-mono-code mr-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Filters:</span>
                            </div>

                            <!-- Date Filter: From and To -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <div class="flex items-center gap-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl px-2.5 py-1 text-xs shadow-2xs transition-colors">
                                    <label for="staffDateFrom" class="text-slate-500 font-bold font-mono-code text-[11px] cursor-pointer">From:</label>
                                    <input type="date" id="staffDateFrom" onchange="filterStaffCaseRows()" oninput="filterStaffCaseRows()" class="bg-transparent border-0 text-xs font-semibold text-slate-700 focus:outline-none cursor-pointer">
                                </div>
                                <div class="flex items-center gap-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl px-2.5 py-1 text-xs shadow-2xs transition-colors">
                                    <label for="staffDateTo" class="text-slate-500 font-bold font-mono-code text-[11px] cursor-pointer">To:</label>
                                    <input type="date" id="staffDateTo" onchange="filterStaffCaseRows()" oninput="filterStaffCaseRows()" class="bg-transparent border-0 text-xs font-semibold text-slate-700 focus:outline-none cursor-pointer">
                                </div>
                            </div>

                            <!-- Priority Filter -->
                            <div class="relative">
                                <select id="staffPriorityFilter" onchange="filterStaffCaseRows()" class="appearance-none pl-3 pr-8 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:bg-white focus:border-purple-600 transition-colors cursor-pointer shadow-2xs">
                                    <option value="all">All Priorities</option>
                                    <option value="high">High</option>
                                    <option value="low">Low</option>
                                </select>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </div>

                            <!-- Approval Status Filter -->
                            <div class="relative">
                                <select id="staffApprovalFilter" onchange="filterStaffCaseRows()" class="appearance-none pl-3 pr-8 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:bg-white focus:border-purple-600 transition-colors cursor-pointer shadow-2xs">
                                    <option value="all">All Approval States</option>
                                    <option value="pending">Pending Approval</option>
                                    <option value="approved">Approved</option>
                                    <option value="head">Head Created</option>
                                </select>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </div>

                            <!-- Clear / Reset Filter Button -->
                            <button type="button" onclick="resetStaffCaseFilters()" title="Reset Filters" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Reset</span>
                            </button>
                        </div>

                        <div class="relative w-full sm:max-w-xs">
                            <input type="text" id="tableFilterInput" onkeyup="filterStaffCaseRows()" placeholder="Filter by Case #, IMEI, Model..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-purple-600 transition-colors">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- CASES TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="staffCasesTable">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-4 font-extrabold">Case Ref</th>
                                    <th class="py-3.5 px-4 font-extrabold">Case Title</th>
                                    <th class="py-3.5 px-4 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-4 font-extrabold">Suspect Disposition</th>
                                    <th class="py-3.5 px-4 font-extrabold text-center">Case Stat</th>
                                    <th class="py-3.5 px-4 font-extrabold text-center">Case Approval Stat</th>
                                    <th class="py-3.5 px-4 font-extrabold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="staffCasesTableBody">
                                @foreach($pendingStaffSubmissions as $sub)
                                    @php
                                        $subPriorityClass = match (strtoupper($sub->priority ?? 'HIGH')) {
                                            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
                                            'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'LOW' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'MEDIUM' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'URGENT' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-200 text-slate-700 border-slate-300',
                                        };
                                        $subSuspect = $sub->suspect_name ?: 'Unassigned Target';
                                        $rawSubDate = $sub->created_at ?? ($sub->incident_date ?? null);
                                        try {
                                            $subDate = $rawSubDate ? \Carbon\Carbon::parse($rawSubDate)->format('Y-m-d') : date('Y-m-d');
                                        } catch (\Throwable $e) {
                                            $subDate = date('Y-m-d');
                                        }
                                        $subIsEdit = !empty($sub->payload['is_edit_submission']) || (($sub->payload['submission_type'] ?? '') === 'edit');
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition-colors staff-case-row" data-caseref="{{ strtolower($sub->case_number_preview ?? ('sub-' . $sub->id)) }}" data-suspect="{{ strtolower($subSuspect) }}" data-operation="{{ strtolower($sub->title ?? '') }}" data-status="pending" data-approval="pending" data-priority="{{ strtolower($sub->priority ?? 'high') }}" data-date="{{ $subDate }}">
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-bold font-mono-code text-blue-600 text-xs">
                                                {{ $sub->case_number_preview ?? ('SUB-' . $sub->id) }}
                                            </div>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $subPriorityClass }}">
                                                    {{ strtoupper($sub->priority ?? 'HIGH') }}
                                                </span>
                                                @if($subIsEdit)
                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold font-mono-code bg-amber-100 text-amber-950 border border-amber-300">
                                                        Edited Case
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-semibold text-slate-800">{{ $sub->title ?? 'Staff Case Submission' }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">
                                                {{ $sub->unit_name ?? 'Digital & Physical Forensics' }}
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                    {{ strtoupper(substr($subSuspect, 0, 2)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="{{ $subSuspect }}">{{ $subSuspect }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">
                                                        Submitted by {{ $sub->submitted_by_name ?? 'Staff Officer' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border bg-slate-100 text-slate-700 border-slate-300">
                                                Active — Under Investigation
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border bg-amber-50 text-amber-800 border-amber-300">
                                                Under Investigation
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            @if($subIsEdit)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border bg-amber-100 text-amber-950 border-amber-300">
                                                    Pending Edit Approval
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border bg-amber-100 text-amber-800 border-amber-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1.5 bg-amber-50 text-amber-700 font-bold rounded-lg border border-amber-200 text-[11px]">
                                                Awaiting Head Review
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                @forelse($cases as $case)
                                    @php
                                        $priorityClass = match (strtoupper($case->priority ?? 'HIGH')) {
                                            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
                                            'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'LOW' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'MEDIUM' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'URGENT' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-200 text-slate-700 border-slate-300',
                                        };

                                        $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                                        $devices = $dData['electronic_devices'] ?? [];
                                        $primaryDevice = 'Electronic Exhibit';
                                        $serialImei = '';
                                        if (!empty($devices[0]['device_name'])) {
                                            $primaryDevice = $devices[0]['device_name'];
                                        } elseif (!empty($devices[0]['make_model'])) {
                                            $primaryDevice = $devices[0]['make_model'];
                                        } elseif (!empty($dData['device_type'])) {
                                            $primaryDevice = $dData['device_type'];
                                        } elseif (!empty($case->title)) {
                                            $primaryDevice = $case->title;
                                        }

                                        if (!empty($devices[0]['imei'])) {
                                            $serialImei = 'IMEI: ' . $devices[0]['imei'];
                                        } elseif (!empty($devices[0]['serial_number'])) {
                                            $serialImei = 'S/N: ' . $devices[0]['serial_number'];
                                        }

                                        $suspectDisplayName = $case->suspect_name ?? ($dData['suspects'][0]['suspect_name'] ?? 'Unassigned Target');
                                        $frontPic = $case->mugshot_front
                                            ?? ($case->photo
                                            ?? ($dData['mugshot_front']
                                            ?? ($dData['photo']
                                            ?? ($dData['mugshots']['front']
                                            ?? ($dData['suspects'][0]['mugshot_front']
                                            ?? ($dData['suspects'][0]['photo'] ?? null))))));
                                        if (!$frontPic && !empty($case->evidence_photos)) {
                                            $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                            if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                                        }
                                        $frontPic = \App\Services\CasePhotoResolver::resolveMugshotUrl($frontPic);

                                        $suspectsArray = !empty($dData['suspects']) && is_array($dData['suspects'])
                                            ? array_values(array_filter($dData['suspects'], fn($s) => !empty($s['suspect_name'])))
                                            : [];
                                        if (empty($suspectsArray)) {
                                            $suspectsArray = [[
                                                'suspect_name'  => $suspectDisplayName,
                                                'suspect_alias' => $case->suspect_alias ?? 'N/A',
                                                'mugshot_front' => $frontPic,
                                                'is_associate'  => false,
                                            ]];
                                        }

                                        $suspectPics = [];
                                        foreach ($suspectsArray as $sIdx => $sItem) {
                                            $p = $sItem['mugshot_front']
                                                ?? ($sItem['photo']
                                                ?? ($sItem['mugshots']['front']
                                                ?? ($sIdx === 0 ? $frontPic : null)));
                                            if (!$p && $sIdx === 0 && !empty($case->evidence_photos)) {
                                                $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                                if (!empty($evArr[0])) { $p = $evArr[0]; }
                                            }
                                            $p = \App\Services\CasePhotoResolver::resolveMugshotUrl($p);
                                            $suspectPics[] = [
                                                'name' => $sItem['suspect_name'] ?? 'Suspect #' . ($sIdx + 1),
                                                'alias' => $sItem['suspect_alias'] ?? 'N/A',
                                                'pic' => $p,
                                                'is_associate' => !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect'),
                                            ];
                                        }

                                        $allAliases = array_values(array_filter(array_map(
                                            fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A' ? $sp['alias'] : null,
                                            $suspectPics
                                        )));
                                        $aliasDisplay = !empty($allAliases) ? implode(', ', $allAliases) : ($case->suspect_alias ?? 'N/A');

                                        $statusLower = strtolower($case->status ?? 'triage');
                                        $cStatLanding = $case->case_status ?? 'Under Investigation';
                                        $cStatBadgeCls = match ($cStatLanding) {
                                            'Under Investigation' => 'bg-amber-50 text-amber-800 border-amber-300',
                                            'On Remand' => 'bg-purple-50 text-purple-800 border-purple-300',
                                            'On Bail' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                                            'In Court' => 'bg-blue-50 text-blue-800 border-blue-300',
                                            'Verdict Passed — Imprisoned' => 'bg-rose-50 text-rose-800 border-rose-300',
                                            'Verdict Passed — Acquitted' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                            'Case Dismissed' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'Case Closed' => 'bg-slate-800 text-slate-100 border-slate-700',
                                            default => 'bg-amber-50 text-amber-800 border-amber-300',
                                        };

                                        $rawApproval = strtolower((string) ($case->approval_status ?? ($dData['approval_status'] ?? '')));
                                        $isStaffSubmission = isset($approvedSubmissionNumbers[$case->case_number])
                                            || !empty($dData['submitted_by_staff'])
                                            || ($case->user_id && isset($creatorHeadMap[$case->user_id]) && !$creatorHeadMap[$case->user_id]);
                                        $createdByHead = ($case->user_id && !empty($creatorHeadMap[$case->user_id])) && !$isStaffSubmission;
                                        $isApprovedByHead = !empty($dData['approved_by_head'])
                                            || !empty($case->is_approved)
                                            || !empty($case->approved_by)
                                            || isset($approvedSubmissionNumbers[$case->case_number])
                                            || $rawApproval === 'approved';
                                        $isDeclined = $rawApproval === 'declined' || str_contains($statusLower, 'declined') || str_contains($statusLower, 'rejected');

                                        if ($createdByHead) {
                                            $approvalLabel = 'Head';
                                            $approvalClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                        } elseif ($isApprovedByHead) {
                                            $approvalLabel = 'Approved';
                                            $approvalClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                        } elseif ($isDeclined) {
                                            $approvalLabel = 'Declined';
                                            $approvalClass = 'bg-rose-100 text-rose-800 border-rose-200';
                                        } else {
                                            $approvalLabel = 'Pending';
                                            $approvalClass = 'bg-amber-100 text-amber-800 border-amber-200';
                                        }

                                        $rawCaseDate = $case->incident_date ?? ($case->created_at ?? ($dData['incident_date'] ?? null));
                                        try {
                                            $caseDate = $rawCaseDate ? \Carbon\Carbon::parse($rawCaseDate)->format('Y-m-d') : date('Y-m-d');
                                        } catch (\Throwable $e) {
                                            $caseDate = date('Y-m-d');
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition-colors staff-case-row" data-caseref="{{ strtolower($case->case_number ?? '') }}" data-suspect="{{ strtolower($suspectDisplayName) }}" data-operation="{{ strtolower(($case->title ?? $primaryDevice) . ' ' . $serialImei) }}" data-status="{{ $statusLower }}" data-approval="{{ strtolower($approvalLabel) }}" data-priority="{{ strtolower($case->priority ?? 'high') }}" data-date="{{ $caseDate }}">
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <a href="/Digital_Forensics/staff/cases/{{ $case->case_number }}/review" class="font-bold font-mono-code text-blue-600 text-xs hover:underline">
                                                {{ $case->case_number }}
                                            </a>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">
                                                    {{ strtoupper($case->priority ?? 'HIGH') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-semibold text-slate-800">{{ $case->title ?? $primaryDevice }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">
                                                {{ $case->unit_name ?? 'Digital & Physical Forensics' }}
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex items-center {{ count($suspectPics) > 1 ? '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '' }} shrink-0 py-1">
                                                    @foreach ($suspectPics as $spIdx => $sp)
                                                        <div class="shrink-0 relative group pt-1.5 pb-0.5"
                                                            style="z-index: {{ 10 + $spIdx }};"
                                                            title="{{ $sp['name'] }} {{ $sp['alias'] !== 'N/A' ? "({$sp['alias']})" : '' }}">
                                                            @if ($sp['is_associate'])
                                                                <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code" title="Associate Suspect">A</span>
                                                            @endif
                                                            <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                                                @if (!empty($sp['pic']))
                                                                    <img src="{{ $sp['pic'] }}" alt="{{ $sp['name'] }}" class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">
                                                                @else
                                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                    </svg>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="min-w-0">
                                                    @if (count($suspectPics) > 1)
                                                        <div class="space-y-1">
                                                            @foreach ($suspectPics as $spIdx => $sp)
                                                                <div class="flex items-center gap-1.5 leading-tight">
                                                                    <span class="font-extrabold text-xs {{ $sp['is_associate'] ? 'text-slate-800' : 'text-slate-950 font-black' }} truncate max-w-[170px]" title="{{ $sp['name'] }}">
                                                                        {{ $sp['name'] }}
                                                                    </span>
                                                                    @if ($sp['is_associate'])
                                                                        <span class="px-1.5 py-0.2 rounded text-[8px] font-mono-code font-extrabold bg-amber-100 text-amber-800 border border-amber-300 shrink-0">CO/ASSOC</span>
                                                                    @else
                                                                        <span class="px-1.5 py-0.2 rounded text-[8px] font-mono-code font-extrabold bg-blue-100 text-blue-800 border border-blue-300 shrink-0">PRIMARY</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @if (!empty($aliasDisplay) && $aliasDisplay !== 'N/A')
                                                            <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[200px]">
                                                                <span class="text-slate-400">ALIAS:</span> {{ $aliasDisplay }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="{{ $suspectDisplayName }}">{{ $suspectDisplayName }}</div>
                                                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[180px]">
                                                            <span class="text-slate-400">ALIAS:</span> {{ $aliasDisplay }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            @php
                                                $sDispListLanding = $case->getSuspectsList();
                                            @endphp
                                            <div class="space-y-1">
                                                @foreach ($sDispListLanding as $sObj)
                                                    @php
                                                        $dispVal = $sObj['suspect_disposition'] ?? 'Active — Under Investigation';
                                                        $dispReason = $sObj['disposition_reason'] ?? '';
                                                        $dispBadge = match (true) {
                                                            str_contains($dispVal, 'Cleared') => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                            str_contains($dispVal, 'Released') => 'bg-blue-100 text-blue-800 border-blue-300',
                                                            str_contains($dispVal, 'Reclassified') => 'bg-purple-100 text-purple-800 border-purple-300',
                                                            str_contains($dispVal, 'Referred') => 'bg-amber-100 text-amber-800 border-amber-300',
                                                            default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                        };
                                                    @endphp
                                                    <div class="flex items-center gap-1.5" title="{{ !empty($dispReason) ? 'Reason: ' . $dispReason : $dispVal }}">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border {{ $dispBadge }}">
                                                            @if (count($sDispListLanding) > 1)
                                                                <span class="font-extrabold text-slate-900 mr-1">{{ $sObj['suspect_name'] }}:</span>
                                                            @endif
                                                            {{ $dispVal }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $cStatBadgeCls }}">
                                                {{ $cStatLanding }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border {{ $approvalClass }}">
                                                {{ $approvalLabel }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <div class="relative inline-block text-left staff-action-dropdown-wrapper">
                                                <button type="button"
                                                    onclick="toggleStaffActionDropdown(event, '{{ $case->case_number }}')"
                                                    class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition-all inline-flex items-center justify-center cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                                    title="Actions for Case {{ $case->case_number }}"
                                                    aria-label="Actions for Case {{ $case->case_number }}">
                                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                </button>

                                                <div id="staffActionMenu-{{ $case->case_number }}"
                                                    class="staff-action-menu hidden fixed z-[99999] w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 space-y-1 text-left font-sans">
                                                    <a href="/Digital_Forensics/staff/cases/{{ $case->case_number }}/review"
                                                        class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-sky-50 hover:text-sky-700 rounded-xl flex items-center space-x-2.5 transition-colors group">
                                                        <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 group-hover:bg-sky-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">View Case</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Open case dossier</div>
                                                        </div>
                                                    </a>

                                                    @include('partials.add_associate_action', [
                                                        'case' => $case,
                                                        'variant' => 'menu',
                                                        'unitLabel' => 'Digital & Physical Forensics',
                                                        'associateFrom' => $sidebarUnit,
                                                    ])
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    @if($pendingStaffSubmissions->isEmpty())
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400 font-mono-code text-xs">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                <span>No digital forensics cases found</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                @endforelse
                                <tr id="noMatchingStaffRows" style="display: none;">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            <span class="font-medium text-xs text-slate-500">No cases match the selected filter criteria</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    @if($cases instanceof \Illuminate\Pagination\LengthAwarePaginator && $cases->hasPages())
                        <div class="pt-3 border-t border-slate-100">
                            {{ $cases->links() }}
                        </div>
                    @endif

                </div>

            </main>
        </div>
    </div>

    <script>
        function setStaffStatusFilter(status) {
            // Maintained for backward compatibility
            filterStaffCaseRows();
        }

        function resetStaffCaseFilters() {
            const approvalSelect = document.getElementById('staffApprovalFilter');
            const prioritySelect = document.getElementById('staffPriorityFilter');
            const dateFrom = document.getElementById('staffDateFrom');
            const dateTo = document.getElementById('staffDateTo');
            const tableInput = document.getElementById('tableFilterInput');
            const globalSearch = document.getElementById('globalSearch');

            if (approvalSelect) approvalSelect.value = 'all';
            if (prioritySelect) prioritySelect.value = 'all';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            if (tableInput) tableInput.value = '';
            if (globalSearch) globalSearch.value = '';

            filterStaffCaseRows();
        }

        function filterStaffCaseRows() {
            const query = (document.getElementById('globalSearch')?.value || document.getElementById('tableFilterInput')?.value || '').trim().toLowerCase();
            const approvalFilter = (document.getElementById('staffApprovalFilter')?.value || 'all').toLowerCase();
            const priorityFilter = (document.getElementById('staffPriorityFilter')?.value || 'all').toLowerCase();
            const dateFrom = (document.getElementById('staffDateFrom')?.value || '').trim();
            const dateTo = (document.getElementById('staffDateTo')?.value || '').trim();

            const rows = document.querySelectorAll('.staff-case-row');
            let visibleCount = 0;
            rows.forEach(row => {
                const cRef = row.getAttribute('data-caseref') || '';
                const suspect = row.getAttribute('data-suspect') || '';
                const operation = row.getAttribute('data-operation') || '';
                const approval = row.getAttribute('data-approval') || '';
                const priority = row.getAttribute('data-priority') || '';
                const rowDate = (row.getAttribute('data-date') || '').trim();

                const matchesQuery = !query || cRef.includes(query) || suspect.includes(query) || operation.includes(query);
                const matchesApproval = (approvalFilter === 'all') || approval.includes(approvalFilter);
                const matchesPriority = (priorityFilter === 'all')
                    || (priority === priorityFilter)
                    || (priorityFilter === 'high' && (priority === 'critical' || priority === 'urgent'));

                let matchesDate = true;
                if (dateFrom && (!rowDate || rowDate < dateFrom)) {
                    matchesDate = false;
                }
                if (dateTo && (!rowDate || rowDate > dateTo)) {
                    matchesDate = false;
                }

                if (matchesQuery && matchesApproval && matchesPriority && matchesDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noMatchesRow = document.getElementById('noMatchingStaffRows');
            if (noMatchesRow) {
                noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
        }

        function closeAllStaffActionMenus() {
            document.querySelectorAll('.staff-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }

        function toggleStaffActionDropdown(event, caseNumber) {
            event.stopPropagation();
            const menu = document.getElementById('staffActionMenu-' + caseNumber);
            if (!menu) return;

            const isCurrentlyHidden = menu.classList.contains('hidden');
            closeAllStaffActionMenus();

            if (isCurrentlyHidden) {
                const btnRect = event.currentTarget.getBoundingClientRect();
                menu.classList.remove('hidden');
                const menuRect = menu.getBoundingClientRect();
                const left = Math.max(12, btnRect.right - menuRect.width);
                const spaceBelow = window.innerHeight - btnRect.bottom;
                const top = spaceBelow >= menuRect.height + 12
                    ? btnRect.bottom + 6
                    : Math.max(12, btnRect.top - menuRect.height - 6);
                menu.style.left = left + 'px';
                menu.style.top = top + 'px';
            }
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.staff-action-dropdown-wrapper')) {
                closeAllStaffActionMenus();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllStaffActionMenus();
            }
        });
    </script>
</body>
</html>

