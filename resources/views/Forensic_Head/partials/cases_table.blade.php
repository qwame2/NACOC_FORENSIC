@php
    $cases = $cases ?? collect();
    $statusParam = strtolower((string) request()->input('status', ($status ?? '')));
    $isLabTestingFilter = str_contains($statusParam, 'lab');
    $isVaultStatusActive = str_contains($statusParam, 'vault');
@endphp
<!-- DEPARTMENT HEAD APPROVAL & CASE REGISTER TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs {{ $isLabTestingFilter ? 'hide-case-stat' : '' }}" id="casesTable">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-5 font-extrabold">Case Reference #</th>
                                    <th class="py-3.5 px-5 font-extrabold">Evidence Details</th>
                                    <th class="py-3.5 px-5 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-5 font-extrabold">Suspect Disposition</th>
                                    @if(!$isLabTestingFilter)
                                        <th class="py-3.5 px-5 font-extrabold text-center col-case-stat">Case Stat</th>
                                    @endif
                                    <th class="py-3.5 px-5 font-extrabold text-right">Actions</th>
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
                                        $frontPic = $case->mugshot_front
                                            ?? ($case->photo
                                            ?? ($dDataHead['mugshot_front']
                                            ?? ($dDataHead['photo']
                                            ?? ($dDataHead['mugshots']['front']
                                            ?? ($dDataHead['suspects'][0]['mugshot_front']
                                            ?? ($dDataHead['suspects'][0]['photo'] ?? null))))));
                                        if (!$frontPic && !empty($case->evidence_photos)) {
                                            $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                            if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                                        }
                                        $frontPic = \App\Services\CasePhotoResolver::resolveMugshotUrl($frontPic);

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
                                                'name'         => $sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1)),
                                                'alias'        => $sItem['suspect_alias'] ?? 'N/A',
                                                'pic'          => $p,
                                                'is_associate' => !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect'),
                                            ];
                                        }
                                        $allAliases = array_values(array_filter(array_map(fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A' ? $sp['alias'] : null, $suspectPics)));
                                        $aliasDisplay = !empty($allAliases) ? implode(', ', $allAliases) : ($case->suspect_alias ?? 'N/A');

                                        $isDrugCase = false;
                                        if (method_exists($case, 'isDrugCase')) {
                                            $isDrugCase = $case->isDrugCase();
                                        }
                                        if (!$isDrugCase && method_exists($case, 'classifyExhibits')) {
                                            $flags = $case->classifyExhibits();
                                            $isDrugCase = !empty($flags['has_drug']);
                                        }
                                        if (!$isDrugCase) {
                                            $txtCheck = strtolower(($case->title ?? '') . ' ' . ($case->exhibits_summary ?? '') . ' ' . ($case->commodity_name ?? '') . ' ' . ($case->substance_name ?? ''));
                                            foreach (['cocaine', 'heroin', 'cannabis', 'weed', 'meth', 'substance', 'narcotic', 'drug', 'powder', 'organic powder', 'white powder', 'crystal'] as $kw) {
                                                if (str_contains($txtCheck, $kw)) {
                                                    $isDrugCase = true;
                                                    break;
                                                }
                                            }
                                        }

                                        $hasPrelimReport = false;
                                        try {
                                            $hasPrelimReport = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id)->exists();
                                        } catch (\Throwable $e) {
                                            $hasPrelimReport = false;
                                        }
                                        $showPrelimReport = ($isDrugCase || $hasPrelimReport);

                                        $isDigitalCase = false;
                                        if (method_exists($case, 'isDigitalCase')) {
                                            $isDigitalCase = $case->isDigitalCase();
                                        }
                                        if (!$isDigitalCase && method_exists($case, 'classifyExhibits')) {
                                            $flags = $case->classifyExhibits();
                                            $isDigitalCase = !empty($flags['has_electronic']);
                                        }
                                        if (!$isDigitalCase) {
                                            $cUnitLower = strtolower($case->unit_name ?? '');
                                            if (str_contains($cUnitLower, 'digital') || str_contains($cUnitLower, 'physical') || str_contains($cUnitLower, 'dpf')) {
                                                $isDigitalCase = true;
                                            }
                                        }
                                        if (!$isDigitalCase) {
                                            try {
                                                $isDigitalCase = \App\Models\DpfReport::forCase($case->id, $case->case_number) !== null;
                                            } catch (\Throwable $e) {}
                                        }

                                        $caseExhibits = method_exists($case, 'getAllCaseExhibits') ? $case->getAllCaseExhibits() : [];
                                        $detailedExhibits = method_exists($case, 'getDetailedSuspectExhibits') ? $case->getDetailedSuspectExhibits() : [];
                                        $suspectModalPayload = [
                                            'case_number' => $case->case_number,
                                            'title' => $case->title ?? 'Case File',
                                            'unit_name' => $case->unit_name ?? 'Forensic Directorate',
                                            'priority' => $case->priority ?? 'HIGH',
                                            'suspect_name' => $suspectDisplayName,
                                            'suspect_alias' => $aliasDisplay ?? 'N/A',
                                            'suspect_disposition' => $sDispNorm ?? 'Under Investigation',
                                            'suspect_pic' => $frontPic,
                                            'raid_location' => $case->raid_location ?: 'Location Undisclosed',
                                            'review_url' => url('/Forensic_Head/cases/' . $case->case_number . '/review'),
                                            'exhibits' => $detailedExhibits,
                                        ];
                                        $rowDateRaw = $case->created_at 
                                            ?: ($case->incident_date ? \Carbon\Carbon::parse($case->incident_date) : ($case->seizure_date ? \Carbon\Carbon::parse($case->seizure_date) : null));
                                        $rowDateStr = $rowDateRaw ? (\Carbon\Carbon::parse($rowDateRaw)->format('Y-m-d')) : '';
                                        $rowDateDisplay = $rowDateStr ?: date('Y-m-d');
                                    @endphp
                                    <tr class="table-row-hover transition-colors case-row" data-ref="{{ $case->case_number }}" data-unit="{{ $case->unit_name }}" data-status="{{ $case->status }}" data-date="{{ $rowDateStr }}" data-is-digital="{{ $isDigitalCase ? '1' : '0' }}" data-is-drug="{{ $showPrelimReport ? '1' : '0' }}" data-has-preliminary="{{ $hasPrelimReport ? '1' : '0' }}" data-has-exhibits="{{ count($caseExhibits) > 0 ? '1' : '0' }}" data-exhibits-count="{{ count($caseExhibits) }}" data-vault-payload="{{ base64_encode(json_encode($suspectModalPayload, JSON_UNESCAPED_UNICODE)) }}">
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            <div class="font-extrabold font-mono-code text-blue-600 text-sm">{{ $case->case_number }}</div>
                                            <div class="flex items-center space-x-1.5 mt-1">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">{{ strtoupper($case->priority ?? 'HIGH') }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono-code">{{ $rowDateDisplay }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $case->title }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $case->holding_facility ?? $case->raid_location ?? $case->seizure_location ?? $case->location ?? 'NACOC Central Custody Facility' }} &bull; {{ $case->exhibits_summary ?? 'Exhibits Logged' }}</div>

                                            @if(!empty($caseExhibits))
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/80 space-y-1.5 vault-exhibits-container {{ $isVaultStatusActive ? '' : 'hidden' }}" style="{{ $isVaultStatusActive ? '' : 'display: none;' }}">
                                                    <div class="flex items-center justify-between text-[10px] font-mono-code font-bold text-slate-600">
                                                        <span class="flex items-center gap-1 text-amber-700">
                                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                            </svg>
                                                            <span>Vault Exhibits ({{ count($caseExhibits) }})</span>
                                                        </span>
                                                        <span class="text-[8.5px] px-1.5 py-0.2 rounded font-black bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wider">In Vault Custody</span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($caseExhibits as $exItem)
                                                            @php
                                                                $exCat = strtolower($exItem['category'] ?? 'general');
                                                                $catBadge = match(true) {
                                                                    str_contains($exCat, 'drug') || str_contains($exCat, 'narcotic') => 'bg-rose-50 text-rose-800 border-rose-200',
                                                                    str_contains($exCat, 'weapon') || str_contains($exCat, 'firearm') => 'bg-amber-50 text-amber-900 border-amber-200',
                                                                    str_contains($exCat, 'device') || str_contains($exCat, 'electron') || str_contains($exCat, 'phone') => 'bg-purple-50 text-purple-800 border-purple-200',
                                                                    str_contains($exCat, 'vehic') || str_contains($exCat, 'car') => 'bg-blue-50 text-blue-800 border-blue-200',
                                                                    str_contains($exCat, 'money') || str_contains($exCat, 'cash') || str_contains($exCat, 'currency') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                                    default => 'bg-slate-100 text-slate-800 border-slate-200',
                                                                };
                                                                $exNo = $exItem['exhibit_number'] ?? null;
                                                                $exName = $exItem['commodity_name'] ?? 'Exhibit Item';
                                                                $exQty = $exItem['quantity_or_weight'] ?? null;
                                                            @endphp
                                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code border {{ $catBadge }} shadow-2xs"
                                                                 title="{{ $exName }} &bull; {{ $exItem['category'] ?? 'General' }} &bull; {{ $exItem['custodial_status'] ?? 'In Vault Custody' }}">
                                                                @if($exNo)
                                                                    <span class="font-black text-slate-900">{{ $exNo }}:</span>
                                                                @endif
                                                                <span class="font-bold truncate max-w-[170px]">{{ $exName }}</span>
                                                                @if($exQty)
                                                                    <span class="opacity-75 font-semibold">({{ $exQty }})</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if($showPrelimReport)
                                                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                                    <a href="{{ url('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number) }}"
                                                       target="_blank"
                                                       class="preliminary-report-pdf-link inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10.5px] font-mono-code font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 border border-rose-200/90 shadow-2xs transition-all text-decoration-none group/pdf"
                                                       title="View Preliminary Exhibit Analysis Report PDF for {{ $case->case_number }}">
                                                        <svg class="w-3.5 h-3.5 text-rose-600 group-hover/pdf:scale-110 transition-transform shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span class="font-bold">Preliminary Exhibit Analysis Report (PDF)</span>
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[8.5px] font-black bg-rose-200/70 text-rose-800 font-mono-code">PDF</span>
                                                    </a>
                                                </div>
                                            @endif
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
                                        @if(!$isLabTestingFilter)
                                            <td class="py-4 px-5 text-center whitespace-nowrap col-case-stat">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $cStatHeadBadge }}">
                                                    {{ $cStatHead }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="py-4 px-5 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5 justify-end">
                                                @if($showPrelimReport)
                                                    <a href="{{ url('/Lab_Analysis/preliminary-report-pdf/' . $case->case_number) }}"
                                                       target="_blank"
                                                       title="Preliminary Exhibit Analysis Report PDF ({{ $case->case_number }})"
                                                       aria-label="Preliminary Exhibit Analysis Report PDF"
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200/80 transition-all shadow-2xs hover:scale-105 active:scale-95">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </a>
                                                @endif
                                                @php
                                                    $headVaultUrl = url('/Forensic_Head/cases/' . $case->case_number . '/vault-exhibits');
                                                    $headReviewUrl = url('/Forensic_Head/cases/' . $case->case_number . '/review');
                                                    $eyeHref = $isVaultStatusActive ? $headVaultUrl : $headReviewUrl;
                                                    $eyeTitle = $isVaultStatusActive ? 'View Vault Storage Exhibit Dossier' : 'View Suspect Case File';
                                                @endphp
                                                <button type="button"
                                                        onclick="openSuspectReportPreview('{{ $case->case_number }}')"
                                                        title="Print Suspect & Exhibit Report ({{ $case->case_number }})"
                                                        aria-label="Print Suspect Report"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200/80 transition-all shadow-2xs hover:scale-105 active:scale-95 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                    </svg>
                                                </button>
                                                <a href="{{ $eyeHref }}"
                                                   data-vault-url="{{ $headVaultUrl }}"
                                                   data-review-url="{{ $headReviewUrl }}"
                                                   title="{{ $eyeTitle }}"
                                                   aria-label="{{ $eyeTitle }}"
                                                   class="case-review-btn inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200/80 transition-all shadow-2xs hover:scale-105 active:scale-95"
                                                   onclick="handleCaseEyeClick(event, this)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isLabTestingFilter ? 5 : 6 }}" class="py-12 text-center text-slate-500 font-mono-code text-xs empty-cases-col">
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

                    <!-- AJAX TABLE METADATA SYNC -->
                    <div id="ajaxTableMeta" class="hidden"
                         data-page-count="{{ count($cases) }}"
                         data-total-count="{{ method_exists($cases, 'total') ? $cases->total() : count($cases) }}"
                         data-per-page="{{ method_exists($cases, 'perPage') ? $cases->perPage() : 15 }}"
                         data-current-page="{{ method_exists($cases, 'currentPage') ? $cases->currentPage() : 1 }}"></div>

                    @if(method_exists($cases, 'total'))
                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-3 text-xs text-slate-500 font-mono-code flex-wrap">
                                <div>
                                    Showing <span class="font-bold text-slate-900">{{ $cases->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-900">{{ $cases->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $cases->total() }}</span> case register dockets
                                </div>
                                <div class="flex items-center gap-1.5 pl-3 border-l border-slate-300">
                                    <label for="perPageSelect" class="text-[11px] text-slate-500 font-medium">Per page:</label>
                                    <select id="perPageSelect" onchange="changePerPage(this.value)" class="bg-white border border-slate-300 text-slate-800 text-xs rounded-lg px-2.5 py-1 focus:ring-1 focus:ring-blue-500 focus:outline-none cursor-pointer">
                                        <option value="15" {{ $cases->perPage() == 15 ? 'selected' : '' }}>15</option>
                                        <option value="25" {{ $cases->perPage() == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $cases->perPage() == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $cases->perPage() == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="case-pagination-links">
                                @if($cases->hasPages())
                                    {{ $cases->links() }}
                                @endif
                            </div>
                        </div>
                    @elseif(method_exists($cases, 'links') && $cases->hasPages())
                        <div class="border-t border-slate-200">
                            {{ $cases->links() }}
                        </div>
                    @endif