@php
    $cases = $cases ?? collect();
    $dpfReportsByCase = $dpfReportsByCase ?? (
        (\Illuminate\Support\Facades\Schema::hasTable('dpf_report') && count($cases) > 0)
            ? \App\Models\DpfReport::whereIn('case_number', collect(method_exists($cases, 'items') ? $cases->items() : $cases)->pluck('case_number')->filter()->all())
                ->orderByDesc('id')
                ->get()
                ->groupBy('case_number')
            : collect()
    );

    $firstItemNum = method_exists($cases, 'firstItem') ? ($cases->firstItem() ?? 0) : (count($cases) > 0 ? 1 : 0);
    $lastItemNum = method_exists($cases, 'lastItem') ? ($cases->lastItem() ?? 0) : count($cases);
    $totalFilteredNum = method_exists($cases, 'total') ? $cases->total() : count($cases);
@endphp

<div id="dpfRegisterTableMeta"
     class="hidden"
     data-first-item="{{ $firstItemNum }}"
     data-last-item="{{ $lastItemNum }}"
     data-total-filtered="{{ $totalFilteredNum }}"
     @if(isset($registerStats))
         data-stat-total="{{ $registerStats['total_cases'] ?? 0 }}"
         data-stat-devices="{{ $registerStats['total_devices'] ?? 0 }}"
         data-stat-extracted="{{ $registerStats['extracted_cases'] ?? 0 }}"
         data-stat-pending="{{ $registerStats['pending_extraction'] ?? 0 }}"
         data-stat-warrant="{{ $registerStats['court_order_cases'] ?? 0 }}"
         data-stat-multi="{{ $registerStats['multi_subject_cases'] ?? 0 }}"
         data-stat-rate="{{ $registerStats['extraction_rate'] ?? 0 }}"
     @endif></div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs text-slate-700" id="regMasterCaseTable">
        <thead>
            <tr class="bg-slate-50/95 border-b border-slate-200/90 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500">
                <th class="py-3.5 px-5 whitespace-nowrap">Docket &amp; Logged</th>
                <th class="py-3.5 px-4">Case Dossier &amp; Subject</th>
                <th class="py-3.5 px-4">Seized Digital Exhibits</th>
                <th class="py-3.5 px-4">Extraction &amp; Warrant</th>
                <th class="py-3.5 px-4">Subject Disposition</th>
                <th class="py-3.5 px-5 text-right whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($cases as $c)
                @php
                    $cDateRaw = !empty($c->incident_date) ? $c->incident_date : ($c->created_at ? $c->created_at->format('Y-m-d') : date('Y-m-d'));
                    $cTime = strtotime((string) $cDateRaw);
                    $cDateIso = ($cTime && $cTime > 0) ? date('Y-m-d', $cTime) : date('Y-m-d');
                    $cDateDisplay = ($cTime && $cTime > 0) ? date('d M Y', $cTime) : date('d M Y');

                    $sDispListDfu = $c->getElectronicDeviceSuspects();
                    if (empty($sDispListDfu)) {
                        $sDispListDfu = $c->getSuspectsList();
                    }

                    $sList = !empty($sDispListDfu) ? $sDispListDfu : [[
                        'suspect_name'  => $c->suspect_name ?? 'Unknown Subject',
                        'suspect_alias' => $c->suspect_alias ?? null,
                        'mugshot_front' => $c->mugshot_front ?? null,
                        'role'          => 'Primary Subject',
                        'is_associate'  => false,
                    ]];
                    $primaryS = $sList[0] ?? [];
                    $primaryName = $primaryS['suspect_name'] ?? ($c->suspect_name ?? 'Unknown Subject');
                    $primaryAlias = $primaryS['suspect_alias'] ?? ($c->suspect_alias ?? null);
                    $allNames = implode(' ', array_filter(array_column($sList, 'suspect_name')));
                    $allAliases = implode(' ', array_filter(array_column($sList, 'suspect_alias')));

                    // Priority & Multi-subject indicators
                    $priorityUpper = \App\Models\ForensicCase::normalizePriority($c->priority ?? 'LOW');
                    $priorityCls = $priorityUpper === 'HIGH'
                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                        : 'bg-slate-100 text-slate-600 border-slate-200';
                    $sceneLabel = trim((string) ($c->raid_location ?: ($c->seizure_location ?? '')));

                    // Electronic exhibits hardware extraction
                    $rawExhibits = ($c->relationLoaded('exhibits') && $c->exhibits) ? collect($c->exhibits) : collect();
                    if ($rawExhibits->isEmpty() && isset($c->underlyingRecord) && isset($c->underlyingRecord->exhibits)) {
                        $rawExhibits = collect($c->underlyingRecord->exhibits);
                    }
                    $elecExhibits = $rawExhibits->filter(function ($ex) {
                        $cat = strtolower((string) ($ex->exhibit_category ?? ''));
                        $nm = strtolower((string) ($ex->commodity_name ?? ''));
                        return !empty($ex->dev_model)
                            || !empty($ex->dev_serial_no)
                            || !empty($ex->dev_imei1)
                            || !empty($ex->dev_imei2)
                            || !empty($ex->dev_mac_address)
                            || str_contains($cat, 'electronic')
                            || str_contains($cat, 'digital')
                            || str_contains($cat, 'device')
                            || str_contains($nm, 'phone')
                            || str_contains($nm, 'iphone')
                            || str_contains($nm, 'samsung')
                            || str_contains($nm, 'laptop')
                            || str_contains($nm, 'drive');
                    })->values();

                    $deviceCount = max(1, $elecExhibits->count());
                    $firstElec = $elecExhibits->first();
                    $primaryHardware = trim((string) (
                        ($firstElec->dev_model ?? null)
                        ?: ($c->dev_model ?? null)
                        ?: ($firstElec->commodity_name ?? null)
                        ?: ($c->seizure_name ?? null)
                        ?: 'Electronic Exhibit'
                    ));
                    $devImei = trim((string) (($firstElec->dev_imei1 ?? null) ?: ($c->dev_imei1 ?? '')));
                    $devSerial = trim((string) (($firstElec->dev_serial_no ?? null) ?: ($c->dev_serial_no ?? '')));
                    $devMac = trim((string) (($firstElec->dev_mac_address ?? null) ?: ($c->dev_mac_address ?? '')));
                    $devStorage = trim((string) (($firstElec->dev_storage ?? null) ?: ($c->dev_storage ?? '')));

                    // DPF Report / Extraction & Court Order status
                    $caseReports = $dpfReportsByCase->get($c->case_number, collect());
                    $dpfRep = $caseReports->first();
                    $extRaw = strtoupper(trim((string) ($dpfRep->extraction_analysis_message ?? ($dpfRep->extraction_analysis_conducted ?? ''))));
                    $courtRaw = strtoupper(trim((string) ($dpfRep->court_order_granted ?? '')));
                    $isExtracted = $dpfRep && (str_contains($extRaw, 'YES') || str_contains($extRaw, 'COMPLET'));
                    $isOngoingExt = !$isExtracted && $dpfRep && (str_contains($extRaw, 'PROGRESS') || str_contains($extRaw, 'ONGOING'));
                    $isWarrantGranted = $dpfRep && str_contains($courtRaw, 'YES');
                    $dpfCode = trim((string) ($dpfRep->dpf_code ?? ''));
                    $hashChecksum = trim((string) ($dpfRep->hash_checksum ?? ''));
                @endphp
                <tr class="group hover:bg-slate-50/90 transition-colors case-register-row"
                    data-date="{{ $cDateIso }}"
                    data-caseref="{{ $c->case_number }}"
                    data-suspect="{{ strtolower($allNames . ' ' . $allAliases) }}"
                    data-user="{{ strtolower($allNames . ' ' . $allAliases) }}">

                    {{-- COL 1: DOCKET & LOGGED DATE --}}
                    <td class="py-4 px-5 align-top whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ url('/cases/' . urlencode($c->case_number) . '/review?from=Digital_Forensics_Register') }}"
                               class="font-mono-code font-extrabold text-xs text-emerald-800 hover:text-emerald-600 hover:underline tracking-tight">
                                {{ $c->case_number }}
                            </a>
                            <button type="button"
                                    onclick="copyCaseNumber('{{ addslashes($c->case_number) }}', this)"
                                    class="opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-all"
                                    aria-label="Copy case number {{ $c->case_number }}"
                                    title="Copy case number">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-mono-code font-bold border {{ $priorityCls }}">
                                {{ $priorityUpper }}
                            </span>
                            @if(!empty($c->is_multiple) || count($sList) > 1)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-mono-code font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                    MULTI ({{ count($sList) }})
                                </span>
                            @endif
                        </div>

                        <div class="mt-2 text-[11px] font-mono-code font-semibold text-slate-700">
                            {{ $cDateDisplay }}
                        </div>
                        @if($sceneLabel !== '')
                            <div class="text-[10.5px] text-slate-500 truncate max-w-[165px] mt-0.5" title="{{ $sceneLabel }}">
                                {{ $sceneLabel }}
                            </div>
                        @endif
                    </td>

                    {{-- COL 2: CASE DOSSIER & CUSTODIAL SUBJECTS --}}
                    <td class="py-4 px-4 align-top min-w-[270px] max-w-[340px]">
                        <div class="flex items-start gap-3">
                            {{-- Stacked Custodial Portraits --}}
                            <div class="flex items-center {{ count($sList) > 1 ? '-space-x-3 hover:space-x-1 transition-all duration-200' : '' }} shrink-0 pt-0.5">
                                @foreach(array_slice($sList, 0, 4) as $sIdx => $sItem)
                                    @php
                                        $rawPic = $sItem['mugshot_front'] ?? ($sIdx === 0 ? $c->mugshot_front : null);
                                        $sPic = null;
                                        if (!empty($rawPic)) {
                                            $rawPic = trim((string) $rawPic);
                                            $sPic = (!str_starts_with($rawPic, 'data:image') && !str_starts_with($rawPic, 'http://') && !str_starts_with($rawPic, 'https://') && !str_starts_with($rawPic, '/'))
                                                ? asset($rawPic)
                                                : $rawPic;
                                        }
                                        $sItemName = $sItem['suspect_name'] ?? 'Subject';
                                        $isAssoc = !empty($sItem['is_associate']);
                                        $sInitialsParts = preg_split('/\s+/', trim((string) $sItemName)) ?: [];
                                        $sInitials = count($sInitialsParts) >= 2
                                            ? strtoupper(substr($sInitialsParts[0], 0, 1) . substr(end($sInitialsParts), 0, 1))
                                            : strtoupper(substr((string) $sItemName, 0, 2));
                                    @endphp
                                    <div class="relative group/mug {{ !empty($sPic) ? 'cursor-pointer' : '' }}"
                                         style="z-index: {{ 10 - $sIdx }};"
                                         @if(!empty($sPic))
                                             role="button"
                                             tabindex="0"
                                             onclick="openMugshotModal('{{ addslashes($sPic) }}', '{{ addslashes($sItemName) }}', '{{ addslashes($c->case_number) }}')"
                                             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openMugshotModal('{{ addslashes($sPic) }}', '{{ addslashes($sItemName) }}', '{{ addslashes($c->case_number) }}');}"
                                         @endif
                                         title="{{ $sItemName }}{{ !empty($sPic) ? ' (Click to enlarge custodial portrait)' : '' }}">
                                        @if($isAssoc)
                                            <span class="absolute -top-1 -right-1 z-20 w-4 h-4 bg-amber-500 border-2 border-white rounded-full flex items-center justify-center text-[8px] font-black text-white font-mono-code shadow-2xs" title="Associate Subject">A</span>
                                        @endif
                                        <div class="w-10 h-12 rounded-xl border border-slate-200/90 bg-slate-100 overflow-hidden shadow-2xs flex items-center justify-center {{ !empty($sPic) ? 'group-hover/mug:ring-2 group-hover/mug:ring-emerald-600' : '' }} transition-all">
                                            @if(!empty($sPic))
                                                <img src="{{ $sPic }}" alt="{{ $sItemName }}" width="40" height="48" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-[10px] font-mono-code font-extrabold text-slate-400 select-none">{{ $sInitials ?: 'NA' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 text-xs leading-snug line-clamp-2">
                                    {{ $c->title ?: 'Digital Forensic Investigation Docket' }}
                                </div>
                                <div class="mt-1.5 flex items-center gap-1.5 flex-wrap text-[11px]">
                                    <span class="text-slate-400 font-medium">Custodian:</span>
                                    <span class="font-mono-code font-bold text-slate-800 uppercase truncate max-w-[175px]" title="{{ $primaryName }}">
                                        {{ $primaryName }}
                                    </span>
                                    @if(!empty($primaryAlias) && strtoupper((string) $primaryAlias) !== 'N/A')
                                        <span class="text-[10.5px] text-slate-500 italic">({{ $primaryAlias }})</span>
                                    @endif
                                    @if(count($sList) > 1)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9.5px] font-mono-code font-bold bg-slate-100 text-slate-700 border border-slate-200" title="{{ count($sList) - 1 }} additional device custodian(s)">
                                            +{{ count($sList) - 1 }} more
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($c->submitting_officer))
                                    <div class="text-[10.5px] text-slate-500 mt-1 truncate">
                                        Officer: <span class="font-semibold text-slate-700">{{ $c->submitting_officer }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- COL 3: SEIZED DIGITAL EXHIBITS --}}
                    <td class="py-4 px-4 align-top min-w-[210px]">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/90 text-slate-800 font-mono-code text-[10px] font-bold">
                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $deviceCount }} {{ $deviceCount === 1 ? 'Device' : 'Devices' }}</span>
                            </span>
                            @if($devStorage !== '')
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 font-mono-code text-[10px] font-semibold">
                                    {{ $devStorage }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-1.5 font-semibold text-slate-800 text-xs truncate max-w-[220px]" title="{{ $primaryHardware }}">
                            {{ $primaryHardware }}
                        </div>

                        <div class="mt-1 space-y-0.5 font-mono-code text-[10px] text-slate-500">
                            @if($devImei !== '')
                                <div class="truncate max-w-[220px]" title="IMEI: {{ $devImei }}">
                                    <span class="text-slate-400">IMEI:</span> <span class="text-slate-700 font-semibold">{{ $devImei }}</span>
                                </div>
                            @elseif($devSerial !== '')
                                <div class="truncate max-w-[220px]" title="Serial: {{ $devSerial }}">
                                    <span class="text-slate-400">S/N:</span> <span class="text-slate-700 font-semibold">{{ $devSerial }}</span>
                                </div>
                            @elseif($devMac !== '')
                                <div class="truncate max-w-[220px]" title="MAC: {{ $devMac }}">
                                    <span class="text-slate-400">MAC:</span> <span class="text-slate-700 font-semibold">{{ $devMac }}</span>
                                </div>
                            @else
                                <div class="text-slate-400">Hardware ID logged in dossier</div>
                            @endif
                        </div>
                    </td>

                    {{-- COL 4: EXTRACTION & WARRANT PIPELINE --}}
                    <td class="py-4 px-4 align-top whitespace-nowrap">
                        <div class="space-y-1.5">
                            @if($isExtracted)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-extrabold">
                                    <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Extraction Complete</span>
                                </span>
                            @elseif($isOngoingExt)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 font-mono-code text-[10px] font-extrabold">
                                    <span>Extraction Ongoing</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-900 font-mono-code text-[10px] font-extrabold">
                                    <span>Pending Extraction</span>
                                </span>
                            @endif

                            <div class="flex items-center gap-1.5 text-[10px] font-mono-code">
                                @if($isWarrantGranted)
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100/70 text-emerald-900 font-bold border border-emerald-200/80">
                                        Warrant: Granted
                                    </span>
                                @elseif($dpfRep)
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold border border-slate-200">
                                        Warrant: {{ $courtRaw ?: 'Pending' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Awaiting DPF status log</span>
                                @endif
                            </div>

                            @if($dpfCode !== '')
                                <div class="text-[10px] font-mono-code text-slate-500">
                                    Ref: <strong class="text-slate-700">{{ $dpfCode }}</strong>
                                </div>
                            @elseif($hashChecksum !== '')
                                <div class="text-[10px] font-mono-code text-slate-500 truncate max-w-[160px]" title="SHA-256: {{ $hashChecksum }}">
                                    Hash: <strong class="text-slate-700">{{ substr($hashChecksum, 0, 12) }}...</strong>
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- COL 5: SUBJECT DISPOSITION --}}
                    <td class="py-4 px-4 align-top whitespace-nowrap">
                        <div class="space-y-1.5">
                            @foreach(array_slice($sDispListDfu, 0, 3) as $sObj)
                                @php
                                    $rawDispVal = (string) ($sObj['suspect_disposition'] ?? 'Active: Under Investigation');
                                    $dispVal = str_replace('—', ':', $rawDispVal);
                                    $dispReason = $sObj['disposition_reason'] ?? '';
                                    $dispBadge = match (true) {
                                        str_contains($dispVal, 'Cleared')      => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        str_contains($dispVal, 'Released')     => 'bg-sky-50 text-sky-800 border-sky-200',
                                        str_contains($dispVal, 'Reclassified') => 'bg-teal-50 text-teal-800 border-teal-200',
                                        str_contains($dispVal, 'Referred')     => 'bg-amber-50 text-amber-900 border-amber-200',
                                        default                                => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <div class="flex items-center gap-1" title="{{ !empty($dispReason) ? 'Reason: ' . $dispReason : $dispVal }}">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code border {{ $dispBadge }}">
                                        @if(count($sDispListDfu) > 1)
                                            <span class="font-extrabold text-slate-900 mr-1">{{ \Illuminate\Support\Str::limit($sObj['suspect_name'] ?? 'Subj', 14) }}:</span>
                                        @endif
                                        {{ $dispVal }}
                                    </span>
                                </div>
                            @endforeach
                            @if(count($sDispListDfu) > 3)
                                <div class="text-[10px] font-mono-code font-semibold text-slate-500">
                                    +{{ count($sDispListDfu) - 3 }} more in dossier
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- COL 6: COMMAND ACTIONS --}}
                    <td class="py-4 px-5 align-top text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <a href="{{ url('/cases/' . urlencode($c->case_number) . '/review?from=Digital_Forensics_Register') }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-200/90 hover:bg-slate-300 text-slate-800 border border-slate-300/80 font-bold text-[11px] rounded-xl shadow-2xs transition-colors">
                                <span>Review File</span>
                                <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>

                            <a href="{{ url('/Digital_Forensics/forensic-status?case=' . urlencode($c->case_number)) }}"
                               class="p-1.5 rounded-xl bg-white hover:bg-emerald-50 text-slate-600 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 transition-colors"
                               aria-label="Open Extraction & Investigation Status for {{ $c->case_number }}"
                               title="Update Extraction & Warrant Status">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </a>

                            <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . rawurlencode($dpfCode !== '' ? $dpfCode : $c->case_number)) }}"
                               class="p-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-600 hover:text-slate-900 border border-slate-200 transition-colors"
                               aria-label="Open DPF Report PDF for {{ $c->case_number }}"
                               title="View / Print DPF Report PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                @php
                    $hasActiveFilters = !empty(request('search'))
                        || (!empty(request('pipeline')) && request('pipeline') !== 'all')
                        || (!empty(request('disposition')) && request('disposition') !== 'all')
                        || !empty(request('from'))
                        || !empty(request('to'));
                @endphp
                <tr>
                    <td colspan="6" class="py-16 px-6 text-center">
                        <div class="max-w-md mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-500 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            @if($hasActiveFilters)
                                <div class="text-sm font-extrabold text-slate-900">
                                    No cyber dockets match your current filters
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Adjust your search query, extraction stage, disposition filter, or date range to view matching Digital Forensics dockets.
                                </p>
                                <div class="pt-1">
                                    <button type="button"
                                            onclick="resetRegDateFilters()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 font-bold text-xs shadow-2xs transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span>Clear all active filters</span>
                                    </button>
                                </div>
                            @else
                                <div class="text-sm font-extrabold text-slate-900">
                                    No digital forensics dockets registered yet
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Dockets containing seized phones, laptops, storage media, or electronic exhibits appear here automatically upon intake.
                                </p>
                                <div class="pt-1">
                                    <a href="{{ url('/Digital_Forensics/create') }}"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>Log New Device Intake</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($cases, 'hasPages') && $cases->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/60 flex items-center justify-between gap-4 flex-wrap text-xs">
        <div class="text-slate-500 font-medium">
            Showing <strong class="text-slate-900 font-mono-code">{{ $firstItemNum }}</strong> to <strong class="text-slate-900 font-mono-code">{{ $lastItemNum }}</strong> of <strong class="text-slate-900 font-mono-code">{{ $totalFilteredNum }}</strong> digital dockets
        </div>
        <div>
            {{ $cases->links() }}
        </div>
    </div>
@endif
