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
@endphp
<!-- TABLE CONTENT -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="casesTable">
                            <thead>
                                <tr
                                    class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-4 font-extrabold">Case Ref</th>
                                    <th class="py-3.5 px-4 font-extrabold">Case Title</th>
                                    <th class="py-3.5 px-4 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-4 font-extrabold">Suspect Disposition</th>
                                    <th class="py-3.5 px-4 font-extrabold text-center">Case Stat</th>
                                    <th class="py-3.5 px-4 font-extrabold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="casesTableBody">
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

                                        $dDataLanding = is_array($case->docket_data)
                                            ? $case->docket_data
                                            : (json_decode($case->docket_data ?? '[]', true) ?:
                                            []);
                                        $uProgLanding = $dDataLanding['unit_progress'] ?? [];

                                        $unitsLandingList = $case->getWorkflowUnits();
                                        $totalUnitsLanding = count($unitsLandingList);

                                        $compCountLanding = 0;
                                        foreach ($unitsLandingList as $ukey) {
                                            if (
                                                isset($uProgLanding[$ukey]['status']) &&
                                                strtoupper($uProgLanding[$ukey]['status']) === 'COMPLETED'
                                            ) {
                                                $compCountLanding++;
                                            }
                                        }

                                        $statusTab = match ($case->status) {
                                            'Approved',
                                            'Court Ready',
                                            'Certified',
                                            'Completed',
                                            'Authorized'
                                                => 'Court Ready',
                                            'Lab Testing', 'Lab Analysis', 'Under Review' => 'Lab Analysis',
                                            default => 'Active Investigation',
                                        };

                                        $statusBadge = match ($case->status) {
                                            'Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized' => [
                                                'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'label' => $case->status,
                                            ],
                                            'Lab Testing', 'Lab Analysis', 'Under Review', 'Awaiting Sign-off' => [
                                                'bg' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'label' => $case->status,
                                            ],
                                            default => [
                                                'bg' => 'bg-white text-slate-900 border-slate-300',
                                                'label' => "Multi-Unit ({$compCountLanding}/{$totalUnitsLanding})",
                                            ],
                                        };

                                        $suspectDisplayName =
                                            $case->suspect_name ?? 'Unassigned Target';

                                        $frontPic = $case->mugshot_front ?? ($dDataLanding['mugshot_front'] ?? null);
                                        if (!$frontPic && !empty($case->evidence_photos)) {
                                            $evArr = is_array($case->evidence_photos)
                                                ? $case->evidence_photos
                                                : (json_decode($case->evidence_photos ?? '[]', true) ?:
                                                []);
                                            if (!empty($evArr[0])) {
                                                $frontPic = $evArr[0];
                                            }
                                        }
                                        if (
                                            $frontPic &&
                                            !str_starts_with($frontPic, 'data:') &&
                                            !str_starts_with($frontPic, 'http://') &&
                                            !str_starts_with($frontPic, 'https://')
                                        ) {
                                            $frontPic = asset($frontPic);
                                        }

                                        $suspectsArray =
                                            !empty($dDataLanding['suspects']) && is_array($dDataLanding['suspects'])
                                                ? array_values(
                                                    array_filter(
                                                        $dDataLanding['suspects'],
                                                        fn($s) => !empty($s['suspect_name']),
                                                    ),
                                                )
                                                : [];

                                        if (empty($suspectsArray)) {
                                            $suspectsArray = [
                                                [
                                                    'suspect_name' => $suspectDisplayName,
                                                    'suspect_alias' => $case->suspect_alias ?? 'N/A',
                                                    'mugshot_front' => $frontPic,
                                                    'is_associate' => false,
                                                ],
                                            ];
                                        }

                                        $suspectPics = [];
                                        foreach ($suspectsArray as $sIdx => $sItem) {
                                            $p = $sItem['mugshot_front'] ?? ($sIdx === 0 ? $frontPic : null);
                                            if (!$p && $sIdx === 0 && !empty($case->evidence_photos)) {
                                                $evArr = is_array($case->evidence_photos)
                                                    ? $case->evidence_photos
                                                    : (json_decode($case->evidence_photos ?? '[]', true) ?:
                                                    []);
                                                if (!empty($evArr[0])) {
                                                    $p = $evArr[0];
                                                }
                                            }
                                            if (
                                                $p &&
                                                !str_starts_with($p, 'data:') &&
                                                !str_starts_with($p, 'http://') &&
                                                !str_starts_with($p, 'https://')
                                            ) {
                                                $p = asset($p);
                                            }
                                            $suspectPics[] = [
                                                'name' => $sItem['suspect_name'] ?? 'Suspect #' . ($sIdx + 1),
                                                'alias' => $sItem['suspect_alias'] ?? 'N/A',
                                                'pic' => $p,
                                                'is_associate' =>
                                                    !empty($sItem['is_associate']) ||
                                                    (!empty($sItem['association_role']) &&
                                                        strtolower($sItem['association_role']) !== 'primary suspect'),
                                            ];
                                        }

                                        $allAliases = array_values(
                                            array_filter(
                                                array_map(
                                                    fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A'
                                                        ? $sp['alias']
                                                        : null,
                                                    $suspectPics,
                                                ),
                                            ),
                                        );
                                        $aliasDisplay = !empty($allAliases)
                                            ? implode(', ', $allAliases)
                                            : $case->suspect_alias ?? 'N/A';
                                    @endphp
                                    <tr class="table-row-hover transition-colors case-row"
                                        data-status="{{ $statusTab }}">
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-bold font-mono-code text-blue-600 text-xs">
                                                {{ $case->case_number }}</div>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">
                                                    {{ strtoupper($case->priority ?? 'HIGH') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-semibold text-slate-800">{{ $case->title }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">
                                                {{ $case->unit_name ?? 'Crime Scene Support' }}
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="flex items-center {{ count($suspectPics) > 1 ? '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '' }} shrink-0 py-1">
                                                    @foreach ($suspectPics as $spIdx => $sp)
                                                        <div class="suspect-avatar-trigger cursor-pointer shrink-0 relative group pt-1.5 pb-0.5"
                                                            style="z-index: {{ 10 + $spIdx }};"
                                                            data-name="{{ $sp['name'] }}"
                                                            data-alias="{{ $sp['alias'] }}"
                                                            data-pic="{{ $sp['pic'] ?? '' }}"
                                                            data-ref="{{ $case->case_number }}"
                                                            data-priority="{{ strtoupper($case->priority ?? 'HIGH') }}"
                                                            title="{{ $sp['name'] }} {{ $sp['alias'] !== 'N/A' ? "({$sp['alias']})" : '' }}">
                                                            @if ($sp['is_associate'])
                                                                <span
                                                                    class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code"
                                                                    title="Associate Suspect">A</span>
                                                            @endif
                                                            <div
                                                                class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                                                @if (!empty($sp['pic']))
                                                                    <img src="{{ $sp['pic'] }}"
                                                                        alt="{{ $sp['name'] }}"
                                                                        class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">
                                                                @else
                                                                    <svg class="w-4 h-4 text-slate-400" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
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
                                        @php
                                            $cStatLanding = $case->case_status ?? 'Under Investigation';
                                            $cStatBadgeCls = match ($cStatLanding) {
                                                'Under Investigation' => 'bg-amber-50 text-amber-800 border-amber-300',
                                                'On Remand' => 'bg-purple-50 text-purple-800 border-purple-300',
                                                'On Bail' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                                                'In Court' => 'bg-blue-50 text-blue-800 border-blue-300',
                                                'Verdict Passed — Imprisoned'
                                                    => 'bg-rose-50 text-rose-800 border-rose-300',
                                                'Verdict Passed — Acquitted'
                                                    => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                                'Case Dismissed' => 'bg-slate-100 text-slate-700 border-slate-300',
                                                'Case Closed' => 'bg-slate-800 text-slate-100 border-slate-700',
                                                default => 'bg-amber-50 text-amber-800 border-amber-300',
                                            };
                                        @endphp
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $cStatBadgeCls }}">
                                                {{ $cStatLanding }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                                <button type="button"
                                                    onclick="toggleCaseActionDropdown(event, '{{ $case->case_number }}')"
                                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                                    title="Actions for Case {{ $case->case_number }}">
                                                    <svg class="w-4 h-4 text-slate-700" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                    <span class="text-xs font-mono-code font-bold">Actions</span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>

                                                <div id="actionMenu-{{ $case->case_number }}"
                                                    class="case-action-menu hidden fixed z-[99999] w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 space-y-1 text-left font-sans">
                                                    <a href="/cases/{{ $case->case_number }}/associate?from={{ urlencode($landingUnitContext) }}"
                                                        class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 hover:text-blue-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div
                                                            class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Add Associate
                                                            </div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                                                Link accomplices &amp; exhibits</div>
                                                        </div>
                                                    </a>

                                                    <a href="/cases/{{ $case->case_number }}/review?edit=true&from={{ urlencode($landingUnitContext) }}"
                                                        class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div
                                                            class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Edit Case</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                                                Modify findings &amp; info</div>
                                                        </div>
                                                    </a>

                                                    <a href="/cases/{{ $case->case_number }}/review?from={{ urlencode($landingUnitContext) }}"
                                                        class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div
                                                            class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">View Case</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                                                Full dossier inspection</div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <p class="font-bold text-slate-700">No forensic case dockets found</p>
                                                <p class="text-[11px] text-slate-400">There are no records matching your query or stored in the database.</p>
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