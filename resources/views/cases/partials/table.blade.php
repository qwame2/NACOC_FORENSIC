@php
    $cases = $cases ?? collect();
    if (!isset($landingUnitContext)) {
        $reqUnitLanding = request()->query('unit') ?? request()->query('from');
        $userUnitLanding = auth()->user()->unit ?? (session('user_unit') ?? '');
        $rawUnit = !empty($reqUnitLanding) ? $reqUnitLanding : (!empty($userUnitLanding) ? $userUnitLanding : 'crime');
        $lower = strtolower((string) $rawUnit);
        if (str_contains($lower, 'lab')) {
            $landingUnitContext = 'Laboratory Analysis (LAU)';
        } elseif (str_contains($lower, 'evidence')) {
            $landingUnitContext = 'Evidence Management (EMU)';
        } elseif (str_contains($lower, 'digital') || str_contains($lower, 'physical')) {
            $landingUnitContext = 'Digital & Physical Forensics (DPFU)';
        } else {
            $landingUnitContext = 'Crime Scene Support';
        }
    }

    $firstItemNum = method_exists($cases, 'firstItem') ? ($cases->firstItem() ?? 0) : (count($cases) > 0 ? 1 : 0);
    $lastItemNum = method_exists($cases, 'lastItem') ? ($cases->lastItem() ?? 0) : count($cases);
    $totalFilteredNum = method_exists($cases, 'total') ? $cases->total() : count($cases);
    $visiblePageCount = count($cases);
@endphp

<div id="casesRegistryTableMeta"
     class="hidden"
     data-first-item="{{ $firstItemNum }}"
     data-last-item="{{ $lastItemNum }}"
     data-total-filtered="{{ $totalFilteredNum }}"
     data-visible-count="{{ $visiblePageCount }}"
     @if(isset($kpiStats))
         data-stat-total="{{ $kpiStats['total'] ?? 0 }}"
         data-stat-lab="{{ $kpiStats['lab_analysis'] ?? 0 }}"
         data-stat-pending="{{ $kpiStats['pending_approval'] ?? 0 }}"
         data-stat-court="{{ $kpiStats['court_certified'] ?? 0 }}"
         data-stat-active="{{ $kpiStats['active_inv'] ?? 0 }}"
     @endif></div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs text-slate-700" id="casesTable">
        <thead>
            <tr class="bg-slate-50/95 border-b border-slate-200/90 text-slate-500 font-mono-code font-bold uppercase text-[10.5px] tracking-wider select-none">
                <th class="py-3.5 px-5 whitespace-nowrap">Case Ref &amp; Logged</th>
                <th class="py-3.5 px-4">Case Dossier &amp; Unit</th>
                <th class="py-3.5 px-4">Custodial Subject(s)</th>
                <th class="py-3.5 px-4">Suspect Disposition</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Case Status</th>
                <th class="py-3.5 px-5 text-right whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white" id="casesTableBody">
            @forelse($cases as $case)
                @php
                    $priorityUpper = \App\Models\ForensicCase::normalizePriority($case->priority ?? 'HIGH');
                    $priorityClass = $priorityUpper === 'HIGH'
                        ? 'bg-amber-100 text-amber-800 border-amber-200'
                        : 'bg-slate-100 text-slate-700 border-slate-300';

                    $dDataLanding = is_array($case->docket_data)
                        ? $case->docket_data
                        : (json_decode($case->docket_data ?? '[]', true) ?: []);

                    $statusTab = match ($case->status) {
                        'Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized' => 'Court Ready',
                        'Lab Testing', 'Lab Analysis', 'Under Review' => 'Lab Analysis',
                        default => 'Active Investigation',
                    };

                    $rawCaseDate = !empty($case->incident_date) ? (string) $case->incident_date : ($case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d'));
                    $caseTs = strtotime($rawCaseDate);
                    $caseDateDisplay = ($caseTs && $caseTs > 0) ? date('d M Y', $caseTs) : date('d M Y');
                    $sceneLoc = trim((string) ($case->raid_location ?: ($case->seizure_location ?? ($case->incident_scene ?? ''))));

                    $suspectDisplayName = $case->suspect_name ?? 'Unassigned Target';

                    $frontPic = $case->mugshot_front
                        ?? ($case->photo
                        ?? ($dDataLanding['mugshot_front']
                        ?? ($dDataLanding['photo']
                        ?? ($dDataLanding['mugshots']['front']
                        ?? ($dDataLanding['suspects'][0]['mugshot_front']
                        ?? ($dDataLanding['suspects'][0]['photo'] ?? null))))));
                    if (!$frontPic && !empty($case->evidence_photos)) {
                        $evArr = is_array($case->evidence_photos)
                            ? $case->evidence_photos
                            : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                        if (!empty($evArr[0])) {
                            $frontPic = $evArr[0];
                        }
                    }
                    $frontPic = \App\Services\CasePhotoResolver::resolveMugshotUrl($frontPic);

                    $suspectsArray = !empty($dDataLanding['suspects']) && is_array($dDataLanding['suspects'])
                        ? array_values(array_filter($dDataLanding['suspects'], fn($s) => !empty($s['suspect_name'])))
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
                            $evArr = is_array($case->evidence_photos)
                                ? $case->evidence_photos
                                : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                            if (!empty($evArr[0])) {
                                $p = $evArr[0];
                            }
                        }
                        $p = \App\Services\CasePhotoResolver::resolveMugshotUrl($p);
                        $suspectPics[] = [
                            'name'         => $sItem['suspect_name'] ?? 'Suspect #' . ($sIdx + 1),
                            'alias'        => $sItem['suspect_alias'] ?? 'N/A',
                            'pic'          => $p,
                            'is_associate' => !empty($sItem['is_associate'])
                                || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect'),
                        ];
                    }

                    $allAliases = array_values(array_filter(array_map(
                        fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A' ? $sp['alias'] : null,
                        $suspectPics
                    )));
                    $aliasDisplay = !empty($allAliases) ? implode(', ', $allAliases) : ($case->suspect_alias ?? 'N/A');
                @endphp
                <tr class="group hover:bg-slate-50/90 transition-colors case-row" data-status="{{ $statusTab }}">
                    {{-- COL 1: CASE REF & LOGGED DATE --}}
                    <td class="py-4 px-5 align-top whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ url('/cases/' . urlencode($case->case_number) . '/review?from=' . urlencode($landingUnitContext)) }}"
                               class="font-bold font-mono-code text-emerald-800 hover:text-emerald-600 hover:underline text-xs tracking-tight">
                                {{ $case->case_number }}
                            </a>
                            <button type="button"
                                    onclick="copyRegistryCaseRef('{{ addslashes($case->case_number) }}')"
                                    class="opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-all"
                                    aria-label="Copy case number {{ $case->case_number }}"
                                    title="Copy case reference">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-mono-code font-bold border {{ $priorityClass }}">
                                {{ $priorityUpper }}
                            </span>
                            @if(!empty($case->is_multiple) || count($suspectPics) > 1)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-mono-code font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                    MULTI ({{ count($suspectPics) }})
                                </span>
                            @endif
                        </div>

                        <div class="mt-1.5 text-[11px] font-mono-code font-semibold text-slate-700">
                            {{ $caseDateDisplay }}
                        </div>
                    </td>

                    {{-- COL 2: CASE DOSSIER & ORIGINATING UNIT --}}
                    <td class="py-4 px-4 align-top min-w-[220px] max-w-[290px]">
                        <div class="font-bold text-slate-900 text-xs leading-snug line-clamp-2">
                            {{ $case->title ?: 'Forensic Investigation Docket' }}
                        </div>
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/90 text-slate-700 font-mono-code text-[10px] font-semibold">
                                {{ $case->unit_name ?? 'Crime Scene Support' }}
                            </span>
                            @if(!empty($case->laboratory_number))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-bold">
                                    {{ $case->laboratory_number }}
                                </span>
                            @endif
                        </div>
                        @if($sceneLoc !== '')
                            <div class="text-[10.5px] text-slate-500 truncate max-w-[260px] mt-1" title="{{ $sceneLoc }}">
                                Scene: <span class="text-slate-700 font-medium">{{ $sceneLoc }}</span>
                            </div>
                        @elseif(!empty($case->submitting_officer))
                            <div class="text-[10.5px] text-slate-500 truncate max-w-[260px] mt-1">
                                Officer: <span class="text-slate-700 font-medium">{{ $case->submitting_officer }}</span>
                            </div>
                        @endif
                    </td>

                    {{-- COL 3: CUSTODIAL SUBJECTS --}}
                    <td class="py-4 px-4 align-top min-w-[230px]">
                        <div class="flex items-start gap-3">
                            <div class="flex items-center {{ count($suspectPics) > 1 ? '-space-x-3 hover:space-x-1 transition-all duration-200' : '' }} shrink-0 pt-0.5">
                                @foreach (array_slice($suspectPics, 0, 4) as $spIdx => $sp)
                                    <div class="suspect-avatar-trigger {{ !empty($sp['pic']) ? 'cursor-pointer' : '' }} shrink-0 relative group/mug"
                                         style="z-index: {{ 10 + $spIdx }};"
                                         data-name="{{ $sp['name'] }}"
                                         data-alias="{{ $sp['alias'] }}"
                                         data-pic="{{ $sp['pic'] ?? '' }}"
                                         data-ref="{{ $case->case_number }}"
                                         data-priority="{{ $priorityUpper }}"
                                         @if(!empty($sp['pic']))
                                             onclick="openMugshotModal('{{ addslashes($sp['pic']) }}', '{{ addslashes($sp['name']) }}', '{{ addslashes($case->case_number) }}')"
                                         @endif
                                         title="{{ $sp['name'] }} {{ $sp['alias'] !== 'N/A' ? "({$sp['alias']})" : '' }}">
                                        @if ($sp['is_associate'])
                                            <span class="absolute -top-1 -right-1 z-30 w-4 h-4 bg-amber-500 border-2 border-white rounded-full flex items-center justify-center text-[8px] font-black text-white shadow-2xs font-mono-code"
                                                  title="Associate Suspect">A</span>
                                        @endif
                                        <div class="w-10 h-11 rounded-xl bg-slate-100 border border-slate-200/90 shadow-2xs flex items-center justify-center font-bold text-slate-600 text-xs overflow-hidden group-hover/mug:ring-2 group-hover/mug:ring-emerald-600 transition-all">
                                            @if (!empty($sp['pic']))
                                                <img src="{{ $sp['pic'] }}"
                                                     alt="{{ $sp['name'] }}"
                                                     width="40"
                                                     height="44"
                                                     class="w-full h-full object-cover object-center">
                                            @else
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="min-w-0">
                                @if (count($suspectPics) > 1)
                                    <div class="space-y-1">
                                        @foreach (array_slice($suspectPics, 0, 3) as $sp)
                                            <div class="flex items-center gap-1.5 leading-tight">
                                                <span class="font-bold text-xs {{ $sp['is_associate'] ? 'text-slate-700' : 'text-slate-900 font-extrabold' }} truncate max-w-[165px]" title="{{ $sp['name'] }}">
                                                    {{ $sp['name'] }}
                                                </span>
                                                @if ($sp['is_associate'])
                                                    <span class="px-1.5 py-0.2 rounded text-[8.5px] font-mono-code font-extrabold bg-amber-50 text-amber-800 border border-amber-200 shrink-0">ASSOC</span>
                                                @else
                                                    <span class="px-1.5 py-0.2 rounded text-[8.5px] font-mono-code font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">PRIMARY</span>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if (count($suspectPics) > 3)
                                            <div class="text-[10px] font-mono-code font-semibold text-slate-500">+{{ count($suspectPics) - 3 }} more</div>
                                        @endif
                                    </div>
                                    @if (!empty($aliasDisplay) && $aliasDisplay !== 'N/A')
                                        <div class="text-[10px] text-slate-500 font-mono-code mt-1 truncate max-w-[195px]">
                                            <span class="text-slate-400">Alias:</span> {{ $aliasDisplay }}
                                        </div>
                                    @endif
                                @else
                                    <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="{{ $suspectDisplayName }}">
                                        {{ $suspectDisplayName }}
                                    </div>
                                    @if (!empty($aliasDisplay) && $aliasDisplay !== 'N/A')
                                        <div class="text-[10px] text-slate-500 font-mono-code mt-1 truncate max-w-[180px]">
                                            <span class="text-slate-400">Alias:</span> {{ $aliasDisplay }}
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-400 font-mono-code mt-1">Primary Subject</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- COL 4: SUSPECT DISPOSITION --}}
                    <td class="py-4 px-4 align-top whitespace-nowrap">
                        @php
                            $sDispListLanding = $case->getSuspectsList();
                        @endphp
                        <div class="space-y-1.5">
                            @foreach (array_slice($sDispListLanding, 0, 3) as $sObj)
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
                                        @if (count($sDispListLanding) > 1)
                                            <span class="font-extrabold text-slate-900 mr-1">{{ \Illuminate\Support\Str::limit($sObj['suspect_name'] ?? 'Subj', 14) }}:</span>
                                        @endif
                                        {{ $dispVal }}
                                    </span>
                                </div>
                            @endforeach
                            @if (count($sDispListLanding) > 3)
                                <div class="text-[10px] font-mono-code font-semibold text-slate-500">
                                    +{{ count($sDispListLanding) - 3 }} more in dossier
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- COL 5: JUDICIAL / CASE STATUS --}}
                    @php
                        $rawCStat = (string) ($case->case_status ?? 'Under Investigation');
                        $cStatLanding = str_replace('—', ':', $rawCStat);
                        $cStatBadgeCls = match ($rawCStat) {
                            'Under Investigation'         => 'bg-amber-50 text-amber-800 border-amber-200',
                            'On Remand'                   => 'bg-teal-50 text-teal-800 border-teal-200',
                            'On Bail'                     => 'bg-sky-50 text-sky-800 border-sky-200',
                            'In Court'                    => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'Verdict Passed — Imprisoned' => 'bg-rose-50 text-rose-800 border-rose-200',
                            'Verdict Passed — Acquitted'  => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'Case Dismissed'              => 'bg-slate-100 text-slate-700 border-slate-200',
                            'Case Closed'                 => 'bg-slate-200 text-slate-800 border-slate-300',
                            default                       => 'bg-amber-50 text-amber-800 border-amber-200',
                        };
                    @endphp
                    <td class="py-4 px-4 align-top text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold border {{ $cStatBadgeCls }}">
                            {{ $cStatLanding }}
                        </span>
                    </td>

                    {{-- COL 6: COMMAND ACTIONS --}}
                    <td class="py-4 px-5 align-top text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <a href="{{ url('/cases/' . urlencode($case->case_number) . '/review?from=' . urlencode($landingUnitContext)) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-200/90 hover:bg-slate-300 text-slate-800 border border-slate-300/80 font-bold text-[11px] transition-colors">
                                <span>Review</span>
                            </a>

                            <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                <button type="button"
                                        onclick="toggleCaseActionDropdown(event, '{{ $case->case_number }}')"
                                        class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-200 transition-colors inline-flex items-center gap-1 shadow-2xs"
                                        aria-label="More actions for Case {{ $case->case_number }}"
                                        title="More actions for Case {{ $case->case_number }}">
                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div id="actionMenu-{{ $case->case_number }}"
                                     class="case-action-menu hidden fixed z-[99999] w-56 bg-white rounded-2xl shadow-xl border border-slate-200 p-2 space-y-1 text-left font-sans">
                                    @include('partials.add_associate_action', [
                                        'case'          => $case,
                                        'variant'       => 'menu',
                                        'menuTheme'     => 'emerald',
                                        'unitLabel'     => $landingUnitContext ?? null,
                                        'associateFrom' => $landingUnitContext ?? '',
                                    ])

                                    @php
                                        $casePending = method_exists($case, 'isPendingApproval') ? $case->isPendingApproval() : false;
                                    @endphp
                                    @if (!$casePending)
                                        <a href="{{ url('/cases/' . urlencode($case->case_number) . '/review?edit=true&from=' . urlencode($landingUnitContext)) }}"
                                           class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-800 rounded-xl flex items-center space-x-2.5 transition-colors group no-underline">
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">Edit Case</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Modify findings &amp; info</div>
                                            </div>
                                        </a>
                                    @else
                                        <div title="Locked: Awaiting review and approval from Head of Unit"
                                             style="opacity: 0.55; cursor: not-allowed;"
                                             class="w-full text-left p-2.5 text-xs font-bold text-slate-400 rounded-xl flex items-center space-x-2.5 select-none">
                                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight flex items-center gap-1.5">
                                                    <span>Edit Case</span>
                                                    <span class="text-[9px] px-1.5 py-0.2 bg-amber-100 text-amber-800 rounded font-mono-code font-bold">LOCKED</span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Awaiting Head approval</div>
                                            </div>
                                        </div>
                                    @endif

                                    <a href="{{ url('/cases/' . urlencode($case->case_number) . '/review?from=' . urlencode($landingUnitContext)) }}"
                                       class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group no-underline">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold leading-tight">View Case</div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Full dossier inspection</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                @php
                    $hasActiveQuery = !empty(request('search'))
                        || (!empty(request('status')) && strtoupper((string) request('status')) !== 'ALL')
                        || (!empty(request('priority')) && strtoupper((string) request('priority')) !== 'ALL')
                        || !empty(request('from_date'))
                        || !empty(request('to_date'))
                        || (!empty(request('unit_filter')) && request('unit_filter') !== 'All Units' && strtolower((string) request('unit_filter')) !== 'all');
                @endphp
                <tr>
                    <td colspan="6" class="py-16 px-6 text-center text-slate-500 text-xs">
                        <div class="max-w-md mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="text-sm font-extrabold text-slate-900">
                                No forensic case dockets found
                            </div>
                            @if($hasActiveQuery)
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    No case dockets match your current search keywords, status stage, priority, or date filter.
                                </p>
                                <div class="pt-1">
                                    <button type="button"
                                            onclick="resetRegistryFilters()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 font-bold text-xs transition-colors">
                                        <span>Clear all active filters</span>
                                    </button>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Registered case dockets, suspect custody profiles, and forensic exhibit records will appear here once logged.
                                </p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($cases, 'links') && $cases->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/60">
        {{ $cases->links() }}
    </div>
@endif
