@php
    $cases = $cases ?? collect();
@endphp
<!-- DEPARTMENT HEAD APPROVAL & CASE REGISTER TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="casesTable">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-5 font-extrabold">Case Reference #</th>
                                    <th class="py-3.5 px-5 font-extrabold">Evidence Details</th>
                                    <th class="py-3.5 px-5 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-5 font-extrabold">Suspect Disposition</th>
                                    <th class="py-3.5 px-5 font-extrabold">Assigned Unit &amp; Head</th>
                                    <th class="py-3.5 px-5 font-extrabold">Handling Officer</th>
                                    <th class="py-3.5 px-5 font-extrabold text-center">Case Stat</th>
                                    <th class="py-3.5 px-5 font-extrabold">Director Status</th>
                                    <th class="py-3.5 px-5 font-extrabold text-right">Head Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="casesTableBody">
                                @forelse($cases as $case)
                                    @php
                                        $priorityClass = match(strtoupper($case->priority ?? 'HIGH')) {
                                            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
                                            'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-300'
                                        };

                                        $unitColor = match($case->unit_name) {
                                            'Evidence Management' => 'text-indigo-600',
                                            'Digital & Physical Forensics' => 'text-amber-600',
                                            'LAB Analysis' => 'text-emerald-600',
                                            default => 'text-blue-600'
                                        };

                                        $dDataHead = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                                        $suspectDisplayName = $case->suspect_name ?? 'Unassigned Target';
                                        $frontPic = $case->mugshot_front ?? ($dDataHead['mugshot_front'] ?? null);
                                        if (!$frontPic && !empty($case->evidence_photos)) {
                                            $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                            if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                                        }
                                        if ($frontPic && !str_starts_with($frontPic, 'data:') && !str_starts_with($frontPic, 'http://') && !str_starts_with($frontPic, 'https://')) {
                                            $frontPic = asset($frontPic);
                                        }

                                        $suspectsArray = !empty($dDataHead['suspects']) && is_array($dDataHead['suspects'])
                                            ? array_values(array_filter($dDataHead['suspects'], fn($s) => !empty($s['suspect_name'])))
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
                                            $p = $sItem['mugshot_front'] ?? ($sIdx === 0 ? $frontPic : null);
                                            if (!$p && $sIdx === 0 && !empty($case->evidence_photos)) {
                                                $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                                if (!empty($evArr[0])) { $p = $evArr[0]; }
                                            }
                                            if ($p && !str_starts_with($p, 'data:') && !str_starts_with($p, 'http://') && !str_starts_with($p, 'https://')) {
                                                $p = asset($p);
                                            }
                                            $suspectPics[] = [
                                                'name'         => $sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1)),
                                                'alias'        => $sItem['suspect_alias'] ?? 'N/A',
                                                'pic'          => $p,
                                                'is_associate' => !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect'),
                                            ];
                                        }
                                        $allAliases = array_values(array_filter(array_map(fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A' ? $sp['alias'] : null, $suspectPics)));
                                        $aliasDisplay = !empty($allAliases) ? implode(', ', $allAliases) : ($case->suspect_alias ?? 'N/A');
                                    @endphp
                                    <tr class="table-row-hover transition-colors case-row" data-unit="{{ $case->unit_name }}" data-status="{{ $case->status }}">
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            <div class="font-extrabold font-mono-code text-blue-600 text-sm">{{ $case->case_number }}</div>
                                            <div class="flex items-center space-x-1.5 mt-1">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">{{ strtoupper($case->priority ?? 'HIGH') }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono-code">{{ $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d') }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $case->title }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $case->holding_facility ?? $case->raid_location ?? $case->seizure_location ?? $case->location ?? 'NACOC Central Custody Facility' }} &bull; {{ $case->exhibits_summary ?? 'Exhibits Logged' }}</div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex items-center {{ count($suspectPics) > 1 ? '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '' }} shrink-0 py-1">
                                                    @foreach($suspectPics as $spIdx => $sp)
                                                        <div class="suspect-avatar-trigger cursor-pointer shrink-0 relative group pt-1.5 pb-0.5" 
                                                             style="z-index: {{ 10 + $spIdx }};"
                                                             data-name="{{ $sp['name'] }}" 
                                                             data-alias="{{ $sp['alias'] }}" 
                                                             data-pic="{{ $sp['pic'] ?? '' }}" 
                                                             data-ref="{{ $case->case_number }}" 
                                                             data-priority="{{ strtoupper($case->priority ?? 'HIGH') }}"
                                                             title="{{ $sp['name'] }} {{ $sp['alias'] !== 'N/A' ? "({$sp['alias']})" : '' }}">
                                                            
                                                            @if($sp['is_associate'])
                                                                <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code" title="Associate Suspect">A</span>
                                                            @endif

                                                            <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                                                @if(!empty($sp['pic']))
                                                                    <img src="{{ $sp['pic'] }}" alt="{{ $sp['name'] }}" class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">
                                                                @else
                                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
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
                                                                    <span class="font-extrabold text-xs {{ $sp['is_associate'] ? 'text-slate-800' : 'text-slate-950 font-black' }} truncate max-w-[170px]"
                                                                        title="{{ $sp['name'] }}">
                                                                        {{ $sp['name'] }}
                                                                    </span>
                                                                    @if ($sp['is_associate'])
                                                                        <span
                                                                            class="px-1.5 py-0.2 rounded text-[8px] font-mono-code font-extrabold bg-amber-100 text-amber-800 border border-amber-300 shrink-0">CO/ASSOC</span>
                                                                    @else
                                                                        <span
                                                                            class="px-1.5 py-0.2 rounded text-[8px] font-mono-code font-extrabold bg-blue-100 text-blue-800 border border-blue-300 shrink-0">PRIMARY</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @if (!empty($aliasDisplay) && $aliasDisplay !== 'N/A')
                                                            <div
                                                                class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[200px]">
                                                                <span class="text-slate-400">ALIAS:</span> {{ $aliasDisplay }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]"
                                                            title="{{ $suspectDisplayName }}">{{ $suspectDisplayName }}
                                                        </div>
                                                        <div
                                                            class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[180px]">
                                                            <span class="text-slate-400">ALIAS:</span> {{ $aliasDisplay }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            @php
                                                $sDispListHead = $case->getSuspectsList();
                                            @endphp
                                            <div class="space-y-1">
                                                @foreach ($sDispListHead as $sObj)
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
                                                            @if (count($sDispListHead) > 1)
                                                                <span class="font-extrabold text-slate-900 mr-1">{{ $sObj['suspect_name'] }}:</span>
                                                            @endif
                                                            {{ $dispVal }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-extrabold {{ $unitColor }} text-xs">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                            <div class="text-[10px] text-slate-600 font-medium">{{ $case->unit_name ? $case->unit_name . ' Unit' : 'Unit Lead' }}</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $case->submitting_officer ?? 'Handling Officer' }}</div>
                                            <div class="text-[10px] text-slate-500">Handling Officer</div>
                                        </td>
                                        @php
                                            $cStatHead = $case->case_status ?? 'Under Investigation';
                                            $cStatHeadBadge = match($cStatHead) {
                                                'Under Investigation'         => 'bg-amber-50 text-amber-800 border-amber-300',
                                                'On Remand'                   => 'bg-purple-50 text-purple-800 border-purple-300',
                                                'On Bail'                     => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                                                'In Court'                    => 'bg-blue-50 text-blue-800 border-blue-300',
                                                'Verdict Passed — Imprisoned' => 'bg-rose-50 text-rose-800 border-rose-300',
                                                'Verdict Passed — Acquitted'  => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                                'Case Dismissed'              => 'bg-slate-100 text-slate-700 border-slate-300',
                                                'Case Closed'                 => 'bg-slate-800 text-slate-100 border-slate-700',
                                                default                       => 'bg-amber-50 text-amber-800 border-amber-300',
                                            };
                                        @endphp
                                        <td class="py-4 px-5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $cStatHeadBadge }}">
                                                {{ $cStatHead }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            @if($case->status === 'Approved' || $case->status === 'Authorized')
                                                <span class="status-badge status-done" id="status-{{ $case->case_number }}">
                                                    <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Authorized
                                                </span>
                                            @elseif($case->status === 'Declined')
                                                <span class="status-badge status-declined" id="status-{{ $case->case_number }}">
                                                    Declined
                                                </span>
                                            @elseif($case->status === 'Under Multi-Unit Processing')
                                                @php
                                                    $dDataHead = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                                                    $uProgHead = $dDataHead['unit_progress'] ?? [];

                                                    $exCatH = strtolower($case->exhibit_category ?? '');
                                                    $sDrugH = $case->seizure_name_drug ?? '';
                                                    $sNameH = strtolower($case->seizure_name ?? '');
                                                    $exSumH = strtolower($case->exhibits_summary ?? '');
                                                    $isDrugHead = (!empty($sDrugH) || 
                                                        str_contains($exCatH, 'drug') || str_contains($exCatH, 'narcotic') || str_contains($exCatH, 'substance') || str_contains($exCatH, 'cannabis') || str_contains($exCatH, 'cocaine') || str_contains($exCatH, 'heroin') || str_contains($exCatH, 'meth') || str_contains($exCatH, 'tramadol') ||
                                                        str_contains($sNameH, 'cocaine') || str_contains($sNameH, 'heroin') || str_contains($sNameH, 'cannabis') || str_contains($sNameH, 'weed') || str_contains($sNameH, 'meth') || str_contains($sNameH, 'tramadol') || str_contains($sNameH, 'drug') || str_contains($sNameH, 'narcotic') ||
                                                        str_contains($exSumH, 'cocaine') || str_contains($exSumH, 'heroin') || str_contains($exSumH, 'cannabis') || str_contains($exSumH, 'weed') || str_contains($exSumH, 'meth') || str_contains($exSumH, 'tramadol') || str_contains($exSumH, 'drug') || str_contains($exSumH, 'narcotic')
                                                    );
                                                    if (!$isDrugHead) {
                                                        $dSeizuresH = $dDataHead['seizures'] ?? [];
                                                        if (is_array($dSeizuresH) && count($dSeizuresH) > 0) {
                                                            foreach ($dSeizuresH as $szH) {
                                                                $catH = strtolower($szH['category'] ?? ($szH['exhibit_category'] ?? ''));
                                                                $dNameH = $szH['seizure_name_drug'] ?? '';
                                                                $szNameH = strtolower($szH['seizure_name'] ?? '');
                                                                if (!empty($dNameH) || str_contains($catH, 'drug') || str_contains($catH, 'narcotic') || str_contains($szNameH, 'cocaine') || str_contains($szNameH, 'heroin') || str_contains($szNameH, 'cannabis') || str_contains($szNameH, 'drug') || str_contains($szNameH, 'narcotic')) {
                                                                    $isDrugHead = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    $isLabCreatedHead = !empty($case->unit_name) && (
                                                        strtolower(trim($case->unit_name)) === 'lab analysis' ||
                                                        str_contains(strtolower($case->unit_name), 'lab analysis') ||
                                                        str_contains(strtolower($case->unit_name), 'laboratory')
                                                    );

                                                     if (method_exists($case, 'getWorkflowUnits')) {
                                                         $unitsHeadList = $case->getWorkflowUnits();
                                                     } elseif ($isDrugHead) {
                                                         $unitsHeadList = ['Lab Analysis', 'Evidence Management'];
                                                     } elseif ($isLabCreatedHead) {
                                                         $unitsHeadList = ['Evidence Management'];
                                                     } else {
                                                         $unitsHeadList = ['Crime Scene Support', 'Evidence Management', 'Digital & Physical Forensics'];
                                                     }
                                                     $tCountHead = count($unitsHeadList);

                                                    $cCountHead = 0;
                                                    foreach($unitsHeadList as $uK) {
                                                        if (isset($uProgHead[$uK]['status']) && strtoupper($uProgHead[$uK]['status']) === 'COMPLETED') $cCountHead++;
                                                    }
                                                    if ($cCountHead === 0) $cCountHead = 1;
                                                @endphp
                                                <span class="status-badge bg-white text-slate-900 border border-slate-300" id="status-{{ $case->case_number }}">
                                                    Multi-Unit ({{ $cCountHead }}/{{ $tCountHead }})
                                                </span>
                                            @else
                                                <span class="status-badge bg-white text-slate-900 border border-slate-300" id="status-{{ $case->case_number }}">
                                                    {{ $case->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-5 text-right whitespace-nowrap">
                                            <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                                <button type="button" 
                                                        onclick="toggleCaseActionDropdown(event, '{{ $case->case_number }}')" 
                                                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                                        title="Actions for Case {{ $case->case_number }}">
                                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                    </svg>
                                                    <span class="text-xs font-mono-code font-bold">Actions</span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>

                                                <div id="actionMenu-{{ $case->case_number }}" class="case-action-menu hidden absolute right-0 mt-1.5 w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 text-left font-sans">
                                                    <!-- 1. ASSOCIATE DATA -->
                                                    <a href="/cases/{{ $case->case_number }}/associate" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 hover:text-blue-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Associate Data</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Link accomplices &amp; exhibits</div>
                                                        </div>
                                                    </a>

                                                    <!-- 2. EDIT DOCKET -->
                                                    <a href="/cases/{{ $case->case_number }}/review?edit=true&from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Edit Docket</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Modify findings &amp; info</div>
                                                        </div>
                                                    </a>

                                                    <!-- 3. VIEW DOCKET -->
                                                    <a href="/cases/{{ $case->case_number }}/review?from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">{{ ($case->status === 'Approved' || $case->status === 'Authorized') ? 'Preview Docket' : 'Review Docket' }}</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Full dossier inspection</div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <p class="font-bold text-slate-700">No case register dockets found</p>
                                                <p class="text-[11px] text-slate-400">There are no records matching your query or awaiting directorate review.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($cases, 'links') && $cases->hasPages())
                        <div class="border-t border-slate-200">
                            {{ $cases->links() }}
                        </div>
                    @endif