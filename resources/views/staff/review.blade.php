@php
    $authUser = auth()->user();
    $staffName = $authUser->fullname ?? session('user_name') ?? 'Staff Officer';
    $sidebarUnit = $sidebarUnit ?? 'Crime Scene Support';

    $rawCtx = strtolower((string) $sidebarUnit);
    if (str_contains($rawCtx, 'lab')) {
        $backUrl = url('/Lab_Analysis/staff/case');
        $accentBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $accentBtn = 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25';
    } elseif (str_contains($rawCtx, 'digital') || str_contains($rawCtx, 'physical')) {
        $backUrl = url('/Digital_Forensics/staff/case');
        $accentBadge = 'bg-purple-50 text-purple-700 border-purple-200';
        $accentBtn = 'bg-purple-600 hover:bg-purple-700 shadow-purple-600/25';
    } elseif (str_contains($rawCtx, 'evidence')) {
        $backUrl = url('/Evidence_Management/staff/case');
        $accentBadge = 'bg-amber-50 text-amber-700 border-amber-200';
        $accentBtn = 'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25';
    } elseif (str_contains($rawCtx, 'directorate') || str_contains($rawCtx, 'head')) {
        $backUrl = url('/Forensic_Head/staff/case');
        $accentBadge = 'bg-blue-50 text-blue-700 border-blue-200';
        $accentBtn = 'bg-blue-600 hover:bg-blue-700 shadow-blue-600/25';
    } else {
        $backUrl = url('/Crime_Scene_Support/staff/case');
        $accentBadge = 'bg-sky-50 text-sky-700 border-sky-200';
        $accentBtn = 'bg-sky-600 hover:bg-sky-700 shadow-sky-600/25';
    }

    $resolveImg = function ($raw) {
        if (empty($raw)) return null;
        $raw = trim((string) $raw);
        if (str_starts_with($raw, 'data:image') || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }
        return asset(ltrim($raw, '/'));
    };

    $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);

    // Build suspect list cleanly
    $suspectsList = method_exists($case, 'getSuspectsList') ? $case->getSuspectsList() : [];
    if (empty($suspectsList) && !empty($dData['suspects']) && is_array($dData['suspects'])) {
        $suspectsList = array_values(array_filter($dData['suspects'], fn($s) => !empty($s['suspect_name'])));
    }
    if (empty($suspectsList)) {
        $suspectsList = [[
            'suspect_name'      => $case->suspect_name ?? 'Unknown Suspect',
            'suspect_alias'     => $case->suspect_alias ?? null,
            'gender'            => $dData['gender'] ?? ($case->gender ?? 'N/A'),
            'dob'               => $dData['dob'] ?? ($case->dob ?? 'N/A'),
            'nationality'       => $dData['nationality'] ?? ($case->nationality ?? 'Ghanaian'),
            'id_number'         => $dData['id_number'] ?? ($dData['ghana_card'] ?? 'N/A'),
            'phone_number'      => $dData['phone_number'] ?? ($case->phone ?? 'N/A'),
            'address'           => $dData['address'] ?? ($case->address ?? 'N/A'),
            'occupation'        => $dData['occupation'] ?? 'N/A',
            'marital_status'    => $dData['marital_status'] ?? 'N/A',
            'disposition'       => $dData['disposition'] ?? ($case->disposition ?? 'In Custody'),
            'date_of_arrest'    => $dData['date_of_arrest'] ?? ($case->incident_date ?? 'N/A'),
            'time_of_arrest'    => $dData['time_of_arrest'] ?? 'N/A',
            'raid_location'     => $dData['raid_location'] ?? ($case->location ?? 'N/A'),
            'arresting_officer' => $dData['arresting_officer'] ?? 'N/A',
            'mugshot_front'     => $case->mugshot_front ?? ($dData['mugshot_front'] ?? null),
            'mugshot_left'      => $case->mugshot_left ?? ($dData['mugshot_left'] ?? null),
            'mugshot_right'     => $case->mugshot_right ?? ($dData['mugshot_right'] ?? null),
            'role'              => 'Primary Suspect',
            'is_associate'      => false,
        ]];
    }

    // Build exhibits list
    $exhibitsList = [];
    if ($case->exhibits && $case->exhibits->count() > 0) {
        foreach ($case->exhibits as $ex) {
            $exhibitsList[] = [
                'item_name'              => $ex->drug_type ?? $ex->exhibit_type ?? $ex->commodity ?? 'Seized Exhibit',
                'category'               => $ex->category ?? 'Contraband',
                'quantity'               => trim(($ex->weight ?? $ex->quantity ?? '') . ' ' . ($ex->weight_unit ?? $ex->unit ?? '')),
                'packaging'              => $ex->packaging ?? 'N/A',
                'concealment'            => $ex->concealment ?? 'N/A',
                'source_of_exhibits'     => $ex->source_of_exhibits ?? 'N/A',
                'investigating_officer'  => $ex->investigating_officer ?? 'N/A',
                'source_requesting_unit' => $ex->source_requesting_unit ?? 'N/A',
                'remark'                 => $ex->exhibit_remark ?? $ex->remarks ?? '',
                'barcode'                => $ex->barcode ?? $ex->tag_number ?? ('TAG-' . substr(md5($case->case_number . ($ex->id ?? '')), 0, 8)),
                'vault_location'         => $ex->vault_location ?? 'Primary Vault',
                'suspect_name'           => $ex->suspect_name ?? ($suspectsList[0]['suspect_name'] ?? 'Joint Ownership'),
                'photos'                 => is_array($ex->photos) ? $ex->photos : (json_decode($ex->photos ?? '[]', true) ?: []),
                'device_imei'            => $ex->imei_1 ?? $ex->imei ?? null,
                'device_serial'          => $ex->serial_number ?? null,
            ];
        }
    }
    if (empty($exhibitsList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
        foreach ($dData['seizures'] as $idx => $sz) {
            $exhibitsList[] = [
                'item_name'              => $sz['drug_type'] ?? $sz['device_name'] ?? $sz['exhibit_type'] ?? $sz['commodity'] ?? 'Seized Exhibit #' . ($idx + 1),
                'category'               => $sz['category'] ?? 'Seized Item',
                'quantity'               => trim(($sz['weight'] ?? $sz['quantity'] ?? '') . ' ' . ($sz['weight_unit'] ?? $sz['unit'] ?? '')),
                'packaging'              => $sz['packaging'] ?? 'N/A',
                'concealment'            => $sz['concealment'] ?? 'N/A',
                'source_of_exhibits'     => $sz['source_of_exhibits'] ?? ($sz['source'] ?? 'N/A'),
                'investigating_officer'  => $sz['investigating_officer'] ?? 'N/A',
                'source_requesting_unit' => $sz['source_requesting_unit'] ?? 'N/A',
                'remark'                 => $sz['exhibit_remark'] ?? ($sz['remarks'] ?? ''),
                'barcode'                => $sz['barcode'] ?? ('TAG-' . substr(md5($case->case_number . $idx), 0, 8)),
                'vault_location'         => $sz['vault_location'] ?? 'Primary Vault',
                'suspect_name'           => $sz['suspect_name'] ?? ($sz['associated_person'] ?? 'Joint Ownership'),
                'photos'                 => $sz['photos'] ?? [],
                'device_imei'            => $sz['imei_1'] ?? ($sz['imei'] ?? null),
                'device_serial'          => $sz['serial_number'] ?? null,
            ];
        }
    }

    $statusLower = strtolower($case->status ?? 'pending');
    $statusBadgeClass = match(true) {
        str_contains($statusLower, 'complete') || str_contains($statusLower, 'approved') || str_contains($statusLower, 'closed') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        str_contains($statusLower, 'progress') || str_contains($statusLower, 'analysis') || str_contains($statusLower, 'active') => 'bg-amber-100 text-amber-800 border-amber-200',
        default => 'bg-slate-100 text-slate-800 border-slate-200',
    };

    $priorityUpper = strtoupper($case->priority ?? 'HIGH');
    $priorityBadgeClass = match($priorityUpper) {
        'CRITICAL', 'URGENT' => 'bg-rose-100 text-rose-800 border-rose-200',
        'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
        default => 'bg-blue-100 text-blue-800 border-blue-200',
    };

    $labResults = $dData['lab_results'] ?? ($dData['preliminary_analysis'] ?? null);
    $gsaReports = \App\Models\SuspectGsaReport::where('case_number', $case->case_number)->get();
    $nlrReports = \App\Models\SuspectNlrReport::where('case_number', $case->case_number)->get();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Case Review — {{ $case->case_number }} | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        .suspect-tab-btn.active {
            background: #0F172A;
            color: #FFFFFF;
            border-color: #0F172A;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
        }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-slate-50 text-slate-900">

    <div class="flex h-screen w-full overflow-hidden">
        {{-- STANDALONE STAFF SIDEBAR --}}
        @include('staff.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- MODERN FLOATING TOPBAR WITH BACKSHADOW & BORDER-RADIUS -->
            <header class="sticky top-2.5 z-40 mx-4 mt-2 mb-1 px-5 py-3 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/90 shadow-[0_10px_25px_-5px_rgba(15,23,42,0.08),0_4px_10px_-2px_rgba(15,23,42,0.04)] flex items-center justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Back to Case Register</span>
                    </a>
                    <div class="hidden sm:flex items-center gap-2 min-w-0">
                        <span class="text-slate-300">|</span>
                        <span class="text-xs font-mono-code font-extrabold text-slate-900 truncate">{{ $case->case_number }}</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full border {{ $statusBadgeClass }}">
                            {{ $case->status ?? 'Pending' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @include('partials.add_associate_action', [
                        'case' => $case,
                        'variant' => 'button',
                        'unitLabel' => null,
                        'associateFrom' => $sidebarUnit,
                    ])
                    <a href="/Forensic_Head/cases/{{ $case->case_number }}/pdf" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 {{ $accentBtn }} text-white font-bold text-xs rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Dossier</span>
                    </a>
                </div>
            </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- CASE OVERVIEW BANNER -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                        <div class="space-y-1.5">
                            <div class="flex items-center flex-wrap gap-2">
                                <span class="px-2.5 py-0.5 text-[10px] font-extrabold font-mono-code rounded-md border uppercase tracking-wider {{ $accentBadge }}">
                                    {{ strtoupper($sidebarUnit) }} | STAFF CASE REVIEW
                                </span>
                                <span class="px-2.5 py-0.5 text-[10px] font-extrabold font-mono-code rounded-md border uppercase {{ $priorityBadgeClass }}">
                                    {{ $priorityUpper }} PRIORITY
                                </span>
                                <span class="px-2.5 py-0.5 text-[10px] font-extrabold font-mono-code rounded-md border uppercase {{ $statusBadgeClass }}">
                                    {{ $case->status ?? 'Pending' }}
                                </span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $case->title ?? 'Forensic Investigation Dossier' }}</h1>
                            <p class="text-xs text-slate-500 font-mono-code">
                                Case Ref: <strong class="text-slate-900">{{ $case->case_number }}</strong>
                                <span class="text-slate-300 mx-1">|</span> Originating Unit: <strong class="text-slate-700">{{ $case->unit_name ?? $sidebarUnit }}</strong>
                                <span class="text-slate-300 mx-1">|</span> Logged: <strong class="text-slate-700">{{ $case->created_at ? $case->created_at->format('d M Y, H:i') : date('d M Y') }}</strong>
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 shrink-0">
                            <div class="px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                                <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Suspects</div>
                                <div class="text-lg font-black text-slate-900 font-mono-code">{{ count($suspectsList) }}</div>
                            </div>
                            <div class="px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                                <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Exhibits</div>
                                <div class="text-lg font-black text-slate-900 font-mono-code">{{ count($exhibitsList) }}</div>
                            </div>
                            <div class="px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-center">
                                <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Reports</div>
                                <div class="text-lg font-black text-slate-900 font-mono-code">{{ $gsaReports->count() + $nlrReports->count() }}</div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($case->description) || !empty($dData['case_summary']))
                    <div class="pt-4">
                        <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-400 tracking-wider mb-1">Case Brief / Operational Summary</div>
                        <p class="text-xs text-slate-700 leading-relaxed">{{ $case->description ?? $dData['case_summary'] }}</p>
                    </div>
                    @endif
                </div>

                <!-- SUSPECT BIOMETRIC & BIO-DATA DOSSIER -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Suspect Biometric &amp; Bio-Data Profile</h2>
                            <p class="text-xs text-slate-500">Review suspect identity, mugshots, arrest details, and custody disposition</p>
                        </div>

                        @if(count($suspectsList) > 1)
                        <div class="flex items-center gap-1.5 flex-wrap" id="suspectTabBar">
                            @foreach($suspectsList as $sIdx => $sItem)
                            <button type="button" onclick="selectReviewSuspect({{ $sIdx }}, this)" class="suspect-tab-btn {{ $sIdx === 0 ? 'active' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 transition-all cursor-pointer">
                                {{ $sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1)) }}
                                @if(!empty($sItem['is_associate']))
                                    <span class="ml-1 text-[9px] opacity-75">(Associate)</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    @foreach($suspectsList as $sIdx => $suspect)
                    @php
                        $sFront = $resolveImg($suspect['mugshot_front'] ?? null);
                        $sLeft  = $resolveImg($suspect['mugshot_left'] ?? null);
                        $sRight = $resolveImg($suspect['mugshot_right'] ?? null);
                        $sName  = $suspect['suspect_name'] ?? 'Unknown Suspect';
                    @endphp
                    <div id="suspectPanel-{{ $sIdx }}" class="suspect-review-panel {{ $sIdx === 0 ? '' : 'hidden' }} grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Mugshot Gallery -->
                        <div class="lg:col-span-4 space-y-3">
                            <div class="aspect-square rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center relative group">
                                @if($sFront)
                                    <img src="{{ $sFront }}" alt="{{ $sName }} Front Mugshot" onclick="openStaffLightbox('{{ $sFront }}', '{{ addslashes($sName) }} — Front Mugshot')" class="w-full h-full object-cover cursor-zoom-in group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="text-center p-6">
                                        <div class="w-20 h-20 mx-auto rounded-2xl bg-slate-200 text-slate-600 font-black text-2xl flex items-center justify-center mb-2">
                                            {{ strtoupper(substr($sName, 0, 2)) }}
                                        </div>
                                        <span class="text-[11px] font-mono-code text-slate-400">No Front Mugshot Uploaded</span>
                                    </div>
                                @endif
                                <span class="absolute top-3 left-3 px-2.5 py-1 bg-slate-900/80 backdrop-blur-xs text-white text-[10px] font-mono-code font-bold rounded-lg">FRONT VIEW</span>
                            </div>

                            @if($sLeft || $sRight)
                            <div class="grid grid-cols-2 gap-3">
                                <div class="aspect-square rounded-xl bg-slate-100 border border-slate-200 overflow-hidden relative group flex items-center justify-center">
                                    @if($sLeft)
                                        <img src="{{ $sLeft }}" alt="Left Profile" onclick="openStaffLightbox('{{ $sLeft }}', '{{ addslashes($sName) }} — Left Profile')" class="w-full h-full object-cover cursor-zoom-in">
                                    @else
                                        <span class="text-[10px] font-mono-code text-slate-400">No Left</span>
                                    @endif
                                    <span class="absolute bottom-2 left-2 px-1.5 py-0.5 bg-slate-900/75 text-white text-[9px] font-mono-code rounded">LEFT</span>
                                </div>
                                <div class="aspect-square rounded-xl bg-slate-100 border border-slate-200 overflow-hidden relative group flex items-center justify-center">
                                    @if($sRight)
                                        <img src="{{ $sRight }}" alt="Right Profile" onclick="openStaffLightbox('{{ $sRight }}', '{{ addslashes($sName) }} — Right Profile')" class="w-full h-full object-cover cursor-zoom-in">
                                    @else
                                        <span class="text-[10px] font-mono-code text-slate-400">No Right</span>
                                    @endif
                                    <span class="absolute bottom-2 left-2 px-1.5 py-0.5 bg-slate-900/75 text-white text-[9px] font-mono-code rounded">RIGHT</span>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Bio-Data Fields -->
                        <div class="lg:col-span-8 space-y-5">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                <div>
                                    <h3 class="text-xl font-black text-slate-900">{{ $sName }}</h3>
                                    <div class="text-xs text-slate-500 font-mono-code">
                                        Alias: <strong class="text-slate-700">{{ $suspect['suspect_alias'] ?? 'None' }}</strong>
                                        <span class="text-slate-300 mx-1">|</span> Role: <strong class="text-slate-700">{{ $suspect['role'] ?? (!empty($suspect['is_associate']) ? 'Associate / Accomplice' : 'Primary Suspect') }}</strong>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl text-xs font-extrabold font-mono-code">
                                    {{ $suspect['disposition'] ?? 'In Custody' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Gender</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['gender'] ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Date of Birth</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['dob'] ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Nationality</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['nationality'] ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">National ID / Ghana Card</div>
                                    <div class="font-bold text-slate-900 font-mono-code mt-0.5">{{ $suspect['id_number'] ?? ($suspect['ghana_card'] ?? 'N/A') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Phone Contact</div>
                                    <div class="font-bold text-slate-900 font-mono-code mt-0.5">{{ $suspect['phone_number'] ?? ($suspect['phone'] ?? 'N/A') }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Occupation</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['occupation'] ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 col-span-2 sm:col-span-3">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 font-mono-code">Residential Address</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['address'] ?? ($suspect['residential_address'] ?? 'N/A') }}</div>
                                </div>
                            </div>

                            <!-- Arrest Details -->
                            <div class="p-4 bg-slate-50/90 rounded-2xl border border-slate-200/80 space-y-2">
                                <div class="text-[10px] font-extrabold font-mono-code uppercase text-slate-500 tracking-wider">Arrest &amp; Apprehension Details</div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div>
                                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Arrest Date &amp; Time</span>
                                        <span class="font-bold text-slate-800 font-mono-code">{{ $suspect['date_of_arrest'] ?? 'N/A' }} {{ $suspect['time_of_arrest'] ?? '' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Arrest / Raid Location</span>
                                        <span class="font-bold text-slate-800">{{ $suspect['raid_location'] ?? ($case->location ?? 'N/A') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Arresting Officer(s)</span>
                                        <span class="font-bold text-slate-800">{{ $suspect['arresting_officer'] ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- SEIZED EXHIBITS & CONTRABAND MANIFEST -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Seized Commodities &amp; Exhibits Manifest</h2>
                            <p class="text-xs text-slate-500">All physical, narcotic, and electronic exhibits recorded under this case</p>
                        </div>
                        <span class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded-lg text-xs font-mono-code font-bold border border-slate-200">
                            {{ count($exhibitsList) }} Item(s)
                        </span>
                    </div>

                    @if(count($exhibitsList) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($exhibitsList as $eIdx => $ex)
                        <div class="p-4.5 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-[10px] font-mono-code font-bold">#{{ $eIdx + 1 }}</span>
                                        <span class="text-sm font-extrabold text-slate-900">{{ $ex['item_name'] }}</span>
                                    </div>
                                    <div class="text-[11px] font-mono-code text-slate-500 mt-0.5">
                                        Barcode: <strong class="text-slate-800">{{ $ex['barcode'] }}</strong>
                                        <span class="text-slate-300 mx-1">|</span> Owner: <strong class="text-slate-800">{{ $ex['suspect_name'] }}</strong>
                                    </div>
                                </div>
                                @if(!empty($ex['quantity']))
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-mono-code font-extrabold shrink-0">
                                    {{ $ex['quantity'] }}
                                </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 text-xs pt-2 border-t border-slate-200/70">
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Source of Exhibits</span>
                                    <span class="font-bold text-slate-800">{{ $ex['source_of_exhibits'] ?: 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Packaging</span>
                                    <span class="font-bold text-slate-800">{{ $ex['packaging'] ?: 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Investigating Officer</span>
                                    <span class="font-bold text-slate-800">{{ $ex['investigating_officer'] ?: 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Vault / Requesting Unit</span>
                                    <span class="font-bold text-slate-800">{{ $ex['vault_location'] }} / {{ $ex['source_requesting_unit'] ?: 'N/A' }}</span>
                                </div>
                                @if(!empty($ex['device_imei']) || !empty($ex['device_serial']))
                                <div class="col-span-2 p-2 bg-purple-50 rounded-lg border border-purple-200/70">
                                    <span class="text-[10px] font-bold uppercase text-purple-700 block">Digital Device Identifiers</span>
                                    <span class="font-mono-code font-bold text-purple-900">
                                        @if($ex['device_imei']) IMEI: {{ $ex['device_imei'] }} @endif
                                        @if($ex['device_serial']) <span class="text-purple-400 mx-1">|</span> S/N: {{ $ex['device_serial'] }} @endif
                                    </span>
                                </div>
                                @endif
                                @if(!empty($ex['remark']))
                                <div class="col-span-2">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Remarks</span>
                                    <span class="text-slate-700">{{ $ex['remark'] }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-8 text-center text-xs text-slate-400 font-mono-code">
                        No seized exhibits have been recorded on this case yet.
                    </div>
                    @endif
                </div>

                <!-- FORENSIC & LAB EXAMINATION FINDINGS -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Forensic &amp; Laboratory Examination Findings</h2>
                            <p class="text-xs text-slate-500">Preliminary tests, GSA / NLR reports, and analytical conclusions</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-emerald-700">GSA Analytical Reports</div>
                            @if($gsaReports->count() > 0)
                                @foreach($gsaReports as $gsa)
                                <div class="flex items-center justify-between py-1 border-b border-slate-200/60 last:border-0">
                                    <span class="font-bold text-slate-800">{{ $gsa->report_title ?? ($gsa->file_name ?? 'GSA Report #' . $loop->iteration) }}</span>
                                    @if(!empty($gsa->file_path))
                                    <a href="{{ asset($gsa->file_path) }}" target="_blank" class="text-emerald-700 font-bold hover:underline">Open PDF</a>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="text-slate-400 font-mono-code">No GSA report attached yet.</div>
                            @endif
                        </div>

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <div class="text-[10px] font-extrabold font-mono-code uppercase text-blue-700">NLR Examination Results</div>
                            @if($nlrReports->count() > 0)
                                @foreach($nlrReports as $nlr)
                                <div class="py-1 border-b border-slate-200/60 last:border-0">
                                    <div class="font-bold text-slate-800">{{ $nlr->substance_detected ?? ($nlr->conclusion ?? 'NLR Finding Recorded') }}</div>
                                    @if(!empty($nlr->remarks))
                                    <div class="text-slate-500 text-[11px]">{{ $nlr->remarks }}</div>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="text-slate-400 font-mono-code">No NLR examination recorded yet.</div>
                            @endif
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- LIGHTBOX MODAL -->
    <div id="staffLightboxModal" class="fixed inset-0 z-[99999] bg-slate-900/85 backdrop-blur-md hidden flex items-center justify-center p-4" onclick="closeStaffLightbox()">
        <div class="relative max-w-3xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-200" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <span id="staffLightboxTitle" class="font-extrabold text-xs uppercase tracking-wider font-mono-code">Biometric Image Inspection</span>
                <button type="button" onclick="closeStaffLightbox()" class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 bg-slate-950 flex items-center justify-center min-h-[360px] max-h-[80vh] overflow-auto">
                <img id="staffLightboxImage" src="" alt="Evidence Photo" class="max-h-[70vh] max-w-full object-contain rounded-xl shadow-lg">
            </div>
        </div>
    </div>

    <script>
        function selectReviewSuspect(idx, btn) {
            document.querySelectorAll('.suspect-review-panel').forEach(p => p.classList.add('hidden'));
            const target = document.getElementById('suspectPanel-' + idx);
            if (target) target.classList.remove('hidden');

            document.querySelectorAll('.suspect-tab-btn').forEach(b => {
                b.classList.remove('active');
                b.classList.add('bg-slate-100', 'text-slate-700');
            });
            if (btn) {
                btn.classList.add('active');
                btn.classList.remove('bg-slate-100', 'text-slate-700');
            }
        }

        function openStaffLightbox(src, title) {
            const modal = document.getElementById('staffLightboxModal');
            const img = document.getElementById('staffLightboxImage');
            const ttl = document.getElementById('staffLightboxTitle');
            if (modal && img) {
                img.src = src;
                if (ttl) ttl.innerText = title || 'Image Inspection';
                modal.classList.remove('hidden');
            }
        }

        function closeStaffLightbox() {
            const modal = document.getElementById('staffLightboxModal');
            if (modal) modal.classList.add('hidden');
        }
    </script>
</body>
</html>

